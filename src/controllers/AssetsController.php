<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\controllers;

use Craft;
use craft\app\errors\AssetConflictException;
use craft\app\errors\AssetLogicException;
use craft\app\errors\AssetException;
use craft\app\errors\UploadFailedException;
use craft\app\fields\Assets as AssetsField;
use craft\app\helpers\Assets;
use craft\app\helpers\Image;
use craft\app\helpers\Io;
use craft\app\elements\Asset;
use craft\app\helpers\StringHelper;
use craft\app\image\Raster ;
use craft\app\models\VolumeFolder;
use craft\app\web\Controller;
use craft\app\web\UploadedFile;
use yii\helpers\FileHelper;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * The AssetsController class is a controller that handles various actions related to asset tasks, such as uploading
 * files and creating/deleting/renaming files and folders.
 *
 * Note that all actions in the controller except [[actionGenerateTransform]] require an authenticated Craft session
 * via [[Controller::allowAnonymous]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AssetsController extends Controller
{
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected $allowAnonymous = ['generate-transform'];

    // Public Methods
    // =========================================================================

    /**
     * Upload a file
     *
     * @return Response
     * @throws BadRequestHttpException for reasons
     */
    public function actionSaveAsset()
    {
        $this->requireAcceptsJson();

        $uploadedFile = UploadedFile::getInstanceByName('assets-upload');
        $request = Craft::$app->getRequest();
        $assetId = $request->getBodyParam('assetId');
        $folderId = $request->getBodyParam('folderId');
        $fieldId = $request->getBodyParam('fieldId');
        $elementId = $request->getBodyParam('elementId');
        $conflictResolution = $request->getBodyParam('userResponse');

        $newFile = (bool)$uploadedFile && empty($assetId);
        $resolveConflict = !empty($conflictResolution) && !empty($assetId);

        try {
            // Resolving a conflict?
            $assets = Craft::$app->getAssets();
            if ($resolveConflict) {
                // When resolving a conflict, $assetId is the id of the file that was created
                // and is conflicting with an existing file.
                if ($conflictResolution == 'replace') {
                    $assetToReplaceWith = $assets->getAssetById($assetId);
                    $filename = Assets::prepareAssetName($request->getRequiredBodyParam('filename'));

                    $assetToReplace = Asset::find()
                        ->folderId($assetToReplaceWith->folderId)
                        ->filename(Db::escapeParam($filename))
                        ->one();

                    if (!$assetToReplace) {
                        throw new BadRequestHttpException('Asset to be replaced cannot be found');
                    }

                    // Check if the user has the permissions to delete files
                    $this->_requirePermissionByAsset('deleteFilesAndFoldersInVolume',
                        $assetToReplace);

                    if ($assetToReplace->volumeId != $assetToReplaceWith->volumeId) {
                        throw new BadRequestHttpException('Asset to be replaced does not live in the same volume as its replacement');
                    }

                    $assets->replaceAsset($assetToReplace,
                        $assetToReplaceWith);
                } else {
                    if ($conflictResolution == 'cancel') {
                        $assetToDelete = $assets->getAssetById($assetId);

                        if ($assetToDelete) {
                            $this->_requirePermissionByAsset('deleteFilesAndFoldersInVolume', $assetToDelete);
                            Craft::$app->getElements()->deleteElement($assetToDelete);
                        }
                    }
                }

                return $this->asJson(['success' => true]);
            }

            if ($newFile) {
                if ($uploadedFile->hasError) {
                    throw new UploadFailedException($uploadedFile->error);
                }

                if (empty($folderId) && (empty($fieldId) || empty($elementId))) {
                    throw new BadRequestHttpException('No target destination provided for uploading');
                }

                if (empty($folderId)) {
                    $field = Craft::$app->getFields()->getFieldById($fieldId);

                    if (!($field instanceof AssetsField)) {
                        throw new BadRequestHttpException('The field provided is not an Assets field');
                    }

                    $element = $elementId ? Craft::$app->getElements()->getElementById($elementId) : null;
                    $folderId = $field->resolveDynamicPathToFolderId($element);
                }

                if (empty($folderId)) {
                    throw new BadRequestHttpException('The target destination provided for uploading is not valid');
                }

                $folder = $assets->findFolder(['id' => $folderId]);

                if (!$folder) {
                    throw new BadRequestHttpException('The target folder provided for uploading is not valid');
                }

                // Check the permissions to upload in the resolved folder.
                $this->_requirePermissionByFolder('saveAssetInVolume',
                    $folder);

                $pathOnServer = Io::getTempFilePath($uploadedFile->name);
                $result = $uploadedFile->saveAs($pathOnServer);

                if (!$result) {
                    Io::deleteFile($pathOnServer, true);
                    throw new UploadFailedException(UPLOAD_ERR_CANT_WRITE);
                }

                $filename = Assets::prepareAssetName($uploadedFile->name);

                $asset = new Asset();

                // Make sure there are no double spaces, if the filename had a space followed by a
                // capital letter because of Yii's "word" logic.
                $asset->title = str_replace('  ', ' ', StringHelper::toTitleCase(Io::getFilename($filename, false)));

                $asset->newFilePath = $pathOnServer;
                $asset->filename = $filename;
                $asset->folderId = $folder->id;
                $asset->volumeId = $folder->volumeId;

                try {
                    $assets->saveAsset($asset);
                    Io::deleteFile($pathOnServer, true);
                } catch (AssetConflictException $exception) {
                    // Okay, get a replacement name and re-save Asset.
                    $replacementName = $assets->getNameReplacementInFolder($asset->filename,
                        $folder->id);
                    $asset->filename = $replacementName;

                    $assets->saveAsset($asset);
                    Io::deleteFile($pathOnServer, true);

                    return $this->asJson([
                        'prompt' => true,
                        'assetId' => $asset->id,
                        'filename' => $uploadedFile->name
                    ]);
                } // No matter what happened, delete the file on server.
                catch (\Exception $exception) {
                    Io::deleteFile($pathOnServer, true);
                    throw $exception;
                }

                return $this->asJson([
                    'success' => true,
                    'filename' => $asset->filename
                ]);
            } else {
                throw new BadRequestHttpException('Not a new asset');
            }
        } catch (\Exception $exception) {
            return $this->asErrorJson($exception->getMessage());
        }
    }

    /**
     * Replace a file
     *
     * @return Response
     */
    public function actionReplaceFile()
    {
        $this->requireAcceptsJson();
        $assetId = Craft::$app->getRequest()->getBodyParam('assetId');
        $uploadedFile = UploadedFile::getInstanceByName('replaceFile');

        $assets = Craft::$app->getAssets();
        $asset = $assets->getAssetById($assetId);

        // Check if we have the relevant permissions.
        $this->_requirePermissionByAsset('saveAssetInVolume', $asset);
        $this->_requirePermissionByAsset('deleteFilesAndFoldersInVolume',
            $asset);

        try {
            if ($uploadedFile->hasError) {
                throw new UploadFailedException($uploadedFile->error);
            }

            $fileName = Assets::prepareAssetName($uploadedFile->name);
            $pathOnServer = Io::getTempFilePath($uploadedFile->name);
            $result = $uploadedFile->saveAs($pathOnServer);

            if (!$result) {
                Io::deleteFile($pathOnServer, true);
                throw new UploadFailedException(UPLOAD_ERR_CANT_WRITE);
            }

            $assets->replaceAssetFile($asset, $pathOnServer,
                $fileName);
        } catch (\Exception $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asJson(['success' => true, 'assetId' => $assetId]);
    }

    /**
     * Create a folder.
     *
     * @return Response
     * @throws BadRequestHttpException if the parent folder cannot be found
     */
    public function actionCreateFolder()
    {
        $this->requireLogin();
        $this->requireAcceptsJson();
        $request = Craft::$app->getRequest();
        $parentId = $request->getRequiredBodyParam('parentId');
        $folderName = $request->getRequiredBodyParam('folderName');
        $folderName = Assets::prepareAssetName($folderName, false);

        $assets = Craft::$app->getAssets();
        $parentFolder = $assets->findFolder(['id' => $parentId]);

        if (!$parentFolder) {
            throw new BadRequestHttpException('The parent folder cannot be found');
        }

        // Check if it's possible to create subfolders in target Volume.
        $this->_requirePermissionByFolder('createFoldersInVolume',
            $parentFolder);

        try {
            $folderModel = new VolumeFolder();
            $folderModel->name = $folderName;
            $folderModel->parentId = $parentId;
            $folderModel->volumeId = $parentFolder->volumeId;
            $folderModel->path = $parentFolder->path.$folderName.'/';

            $assets->createFolder($folderModel);

            return $this->asJson([
                'success' => true,
                'folderName' => $folderModel->name,
                'folderId' => $folderModel->id
            ]);
        } catch (AssetException $exception) {
            return $this->asErrorJson($exception->getMessage());
        }
    }

    /**
     * Delete a folder.
     *
     * @return Response
     * @throws BadRequestHttpException if the folder cannot be found
     */
    public function actionDeleteFolder()
    {
        $this->requireLogin();
        $this->requireAcceptsJson();
        $folderId = Craft::$app->getRequest()->getRequiredBodyParam('folderId');

        $assets = Craft::$app->getAssets();
        $folder = $assets->getFolderById($folderId);

        if (!$folder) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Check if it's possible to delete objects in the target Volume.
        $this->_requirePermissionByFolder('deleteFilesAndFoldersInVolume',
            $folder);
        try {
            $assets->deleteFoldersByIds($folderId);
        } catch (AssetException $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asJson(['success' => true]);
    }

    /**
     * Rename a folder
     *
     * @return Response
     * @throws BadRequestHttpException if the folder cannot be found
     */
    public function actionRenameFolder()
    {
        $this->requireLogin();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $assets = Craft::$app->getAssets();
        $folderId = $request->getRequiredBodyParam('folderId');
        $newName = $request->getRequiredBodyParam('newName');
        $folder = $assets->getFolderById($folderId);

        if (!$folder) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Check if it's possible to delete objects and create folders in target Volume.
        $this->_requirePermissionByFolder('deleteFilesAndFolders', $folder);
        $this->_requirePermissionByFolder('createFolders', $folder);

        try {
            $newName = Craft::$app->getAssets()->renameFolderById($folderId,
                $newName);
        } catch (\Exception $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asJson(['success' => true, 'newName' => $newName]);
    }


    /**
     * Move an Asset or multiple Assets.
     *
     * @return Response
     * @throws BadRequestHttpException if the asset or the target folder cannot be found
     */
    public function actionMoveAsset()
    {
        $this->requireLogin();

        $request = Craft::$app->getRequest();
        $assetId = $request->getRequiredBodyParam('assetId');
        $folderId = $request->getBodyParam('folderId');
        $filename = $request->getBodyParam('filename');
        $conflictResolution = $request->getBodyParam('userResponse');

        $assets = Craft::$app->getAssets();
        $asset = $assets->getAssetById($assetId);

        if (empty($asset)) {
            throw new BadRequestHttpException('The Asset cannot be found');
        }

        $folder = $assets->getFolderById($folderId);

        if (empty($folder)) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Check if it's possible to delete objects in source Volume and save Assets in target Volume.
        $this->_requirePermissionByAsset('deleteFilesAndFolders', $asset);
        $this->_requirePermissionByFolder('saveAssetInVolume', $folder);

        try {

            if (!empty($filename)) {
                $asset->newFilename = $filename;
                $success = $assets->renameFile($asset);

                return $this->asJson(['success' => $success]);
            }

            if ($asset->folderId != $folderId) {
                if (!empty($conflictResolution)) {
                    $conflictingAsset = Asset::find()
                        ->folderId($folderId)
                        ->filename(Db::escapeParam($asset->filename))
                        ->one();

                    if ($conflictResolution == 'replace') {
                        $assets->replaceAsset($conflictingAsset, $asset, true);
                    } else {
                        if ($conflictResolution == 'keepBoth') {
                            $newFilename = $assets->getNameReplacementInFolder($asset->filename, $folderId);
                            $assets->moveAsset($asset, $folderId, $newFilename);
                        }
                    }
                } else {
                    try {
                        $assets->moveAsset($asset, $folderId);
                    } catch (AssetConflictException $exception) {
                        return $this->asJson([
                            'prompt' => true,
                            'filename' => $asset->filename,
                            'assetId' => $asset->id
                        ]);
                    }
                }
            }
        } catch (\Exception $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asJson(['success' => true]);
    }

    /**
     * Move a folder.
     *
     * @return Response
     * @throws BadRequestHttpException if the folder to move, or the destination parent folder, cannot be found
     */
    public function actionMoveFolder()
    {
        $this->requireLogin();

        $request = Craft::$app->getRequest();
        $folderBeingMovedId = $request->getRequiredBodyParam('folderId');
        $newParentFolderId = $request->getRequiredBodyParam('parentId');
        $conflictResolution = $request->getBodyParam('userResponse');

        $assets = Craft::$app->getAssets();
        $folderToMove = $assets->getFolderById($folderBeingMovedId);
        $destinationFolder = $assets->getFolderById($newParentFolderId);

        if (empty($folderToMove)) {
            throw new BadRequestHttpException('The folder you are trying to move does not exist');
        }

        if (empty($destinationFolder)) {
            throw new BadRequestHttpException('The destination folder does not exist');
        }

        // Check if it's possible to delete objects in source Volume, create folders
        // in target Volume and save Assets in target Volume.
        $this->_requirePermissionByFolder('deleteFilesAndFolders',
            $folderToMove);
        $this->_requirePermissionByFolder('createSubfoldersInAssetSource',
            $destinationFolder);
        $this->_requirePermissionByFolder('saveAssetInVolume',
            $destinationFolder);

        try {
            $removeFromTree = [];

            $sourceTree = $assets->getAllDescendantFolders($folderToMove);

            if (empty($conflictResolution)) {
                $existingFolder = $assets->findFolder([
                    'parentId' => $newParentFolderId,
                    'name' => $folderToMove->name
                ]);

                if ($existingFolder) {
                    // Throw a prompt
                    return $this->asJson([
                        'prompt' => true,
                        'foldername' => $folderToMove->name,
                        'folderId' => $folderBeingMovedId,
                        'parentId' => $newParentFolderId
                    ]);
                } else {
                    // No conflicts, mirror the existing structure
                    $folderIdChanges = Assets::mirrorFolderStructure($folderToMove,
                        $destinationFolder);

                    // Get the file transfer list.
                    $allSourceFolderIds = array_keys($sourceTree);
                    $allSourceFolderIds[] = $folderBeingMovedId;
                    $foundAssets = Asset::find()
                        ->folderId($allSourceFolderIds)
                        ->all();
                    $fileTransferList = Assets::getFileTransferList($foundAssets,
                        $folderIdChanges, $conflictResolution == 'merge');
                }
            } else {
                // Resolving a confclit
                $existingFolder = $assets->findFolder([
                    'parentId' => $newParentFolderId,
                    'name' => $folderToMove->name
                ]);
                $targetTreeMap = [];

                // When merging folders, make sure that we're not overwriting folders
                if ($conflictResolution == 'merge') {
                    $targetTree = $assets->getAllDescendantFolders($existingFolder);
                    $targetPrefixLength = strlen($destinationFolder->path);
                    $targetTreeMap = [];

                    foreach ($targetTree as $existingFolder) {
                        $targetTreeMap[substr($existingFolder->path,
                            $targetPrefixLength)] = $existingFolder->id;
                    }

                    $removeFromTree = [$existingFolder->id];
                } // When replacing, just nuke everything that's in our way
                else {
                    if ($conflictResolution == 'replace') {
                        $removeFromTree = [$existingFolder->id];
                        $assets->deleteFoldersByIds($existingFolder->id);
                    }
                }

                // Mirror the structure, passing along the exsting folder map
                $folderIdChanges = Assets::mirrorFolderStructure($folderToMove,
                    $destinationFolder, $targetTreeMap);

                // Get file transfer list for the progress bar
                $allSourceFolderIds = array_keys($sourceTree);
                $allSourceFolderIds[] = $folderBeingMovedId;
                $foundAssets = Asset::find()
                    ->folderId($allSourceFolderIds)
                    ->all();
                $fileTransferList = Assets::getFileTransferList($foundAssets,
                    $folderIdChanges, $conflictResolution == 'merge');
            }
        } catch (AssetLogicException $exception) {
            return $this->asErrorJson($exception->getMessage());
        }

        return $this->asJson([
            'success' => true,
            'changedIds' => $folderIdChanges,
            'transferList' => $fileTransferList,
            'removeFromTree' => $removeFromTree
        ]);
    }

    /**
     * Return the image editor template.
     *
     * @return Response
     */
    public function actionImageEditor()
    {
        $html = Craft::$app->getView()->renderTemplate('_components/tools/image_editor');

        return $this->asJson(['html' => $html]);
    }

    /**
     * Get the image being edited.
     */
    public function actionEditImage()
    {
        $request = Craft::$app->getRequest();
        $assetId = $request->getRequiredQueryParam('assetId');
        $size = $request->getRequiredQueryParam('size');

        $filePath = Assets::getEditorImagePath($assetId, $size);

        if (!$filePath) {
            throw new BadRequestHttpException('The Asset cannot be found');
        }

        $response = Craft::$app->getResponse();

        $filter = $request->getQueryParam('filter');

        if ($filter) {
            $className = StringHelper::replace($filter, '-', '\\');
            $filter = Craft::$app->getImageEffects()->getFilter($className);
            $filterOptions = $request->getQueryParam('filterOptions', []);
            $imageBlob = $filter->applyAndReturnBlob($filePath, $filterOptions);
            return $response->sendContentAsFile($imageBlob, null, ['inline' => true, 'mimeType' => FileHelper::getMimeTypeByExtension($filePath)]);
        } else {
            return $response->sendFile($filePath, null, ['inline' => true]);
        }
    }

    /**
     * Save an image according to posted parameters.
     *
     * @return Response
     * @throws BadRequestHttpException if some parameters are missing.
     * @throws \Exception if something went wrong saving the Asset.
     */
    public function actionSaveImage() {
        $this->requireLogin();
        $this->requireAcceptsJson();

        $assets = Craft::$app->getAssets();
        $request = Craft::$app->getRequest();

        $assetId = $request->getRequiredBodyParam('assetId');
        $viewportRotation = $request->getRequiredBodyParam('viewportRotation');
        $imageRotation = $request->getRequiredBodyParam('imageRotation');
        $replace = $request->getRequiredBodyParam('replace');
        $filter = $request->getBodyParam('filter');

        $asset = $assets->getAssetById($assetId);

        if (empty($asset)) {
            throw new BadRequestHttpException('The Asset cannot be found');
        }

        $folder = $asset->getFolder();

        if (empty($folder)) {
            throw new BadRequestHttpException('The folder cannot be found');
        }

        // Check the permissions to save in the resolved folder.
        $this->_requirePermissionByAsset('saveAssetInVolume', $asset);

        // If replacing, check for permissions to replace existing Asset files.
        if ($replace) {
            $this->_requirePermissionByAsset('deleteFilesAndFolders', $asset);
        }

        if (!in_array($viewportRotation, [0, 90, 180, 270])) {
            throw new BadRequestHttpException('Viewport rotation must be 0, 90, 180 or 270 degrees');
        }

        $imageCopy = $asset->getCopyOfFile();

        // If filter is set, apply that
        if (!empty($filter)) {
            $className = StringHelper::replace($filter, '-', '\\');
            $filter = Craft::$app->getImageEffects()->getFilter($className);
            $filterOptions = $request->getBodyParam('filterOptions', []);
            $filter->applyAndStore($imageCopy, $filterOptions);
        }

        $imageSize = Image::getImageSize($imageCopy);

        /**
         * @var Raster $image
         */
        $image = Craft::$app->getImages()->loadImage($imageCopy, true, max($imageSize));

        // Deal with straighten rotation first.
        if ($imageRotation) {

            $image->rotate($imageRotation);

            $imageWidth = $imageSize[0];
            $imageHeight = $imageSize[1];

            // Convert the angle to radians
            $angleInRadians = abs(deg2rad($imageRotation));

            // When the image is rotated and scaled up, it forms four right angled
            // triangles on the viewport sides. The adjacency is in relation to the
            // rotation angle.
            $sideTriangleAdjacentLeg = cos($angleInRadians) * $imageHeight;
            $sideTriangleOppositeLeg = sin($angleInRadians) * $imageHeight;
            $bottomTriangleAdjacentLeg = cos($angleInRadians) * $imageWidth;
            $bottomTriangleOppositeLeg = sin($angleInRadians) * $imageWidth;

            // For the rotated image, the side and top/bottom edges are composed like this
            $scaledHeight = $sideTriangleAdjacentLeg + $bottomTriangleOppositeLeg;
            $scaledWidth = $bottomTriangleAdjacentLeg + $sideTriangleOppositeLeg;

            // Now use that to calculate the zoom factor and zoom in.
            $zoomFactor = max($scaledHeight / $imageHeight, $scaledWidth / $imageWidth);
            $image->resize($image->getWidth() * $zoomFactor, $image->getHeight() * $zoomFactor);

            // In all likelihood this part will change as we implement more cropping tools,
            // but for now the cropping takes place in the center.
            $leftOffset = ($image->getWidth() - $imageWidth) / 2;
            $topOffset = ($image->getHeight() - $imageHeight) / 2;

            $image->crop($leftOffset, $leftOffset + $imageWidth, $topOffset, $topOffset + $imageHeight);
        }

        // Now, rotate by viewport rotation degrees. We do this after so that the actual aspet ratio of the
        // image changes as well, if it was not square.
        $image->rotate($viewportRotation);
        $image->saveAs($imageCopy);

        if ($replace) {
            $assets->replaceAssetFile($asset, $imageCopy, $asset->filename);
            $asset->dateModified = Io::getLastTimeModified($imageCopy);
            $assetToSave = $asset;
        } else {
            $newAsset = new Asset();
            // Make sure there are no double spaces, if the filename had a space followed by a
            // capital letter because of Yii's "word" logic.
            $newAsset->title = str_replace('  ', ' ', StringHelper::toTitleCase(Io::getFilename($asset->filename, false)));

            $newAsset->newFilePath = $imageCopy;
            $newAsset->filename = $assets->getNameReplacementInFolder($asset->filename, $folder->id);
            $newAsset->folderId = $folder->id;
            $newAsset->volumeId = $folder->volumeId;

            $assetToSave = $newAsset;
        }

        try {
            $assets->saveAsset($assetToSave);
            Io::deleteFile($imageCopy, true);
        } // No matter what happened, delete the file on server.
        catch (\Exception $exception) {
            Io::deleteFile($imageCopy, true);
            throw $exception;
        }

        return $this->asJson(['success' => true]);
    }

    /**
     * Download a file.
     *
     * @return void
     * @throws BadRequestHttpException if the file to download cannot be found.
     */
    public function actionDownloadAsset()
    {
        $this->requireLogin();
        $this->requirePostRequest();

        $assetId = Craft::$app->getRequest()->getRequiredBodyParam('assetId');
        $assetService = Craft::$app->getAssets();

        $asset = $assetService->getAssetById($assetId);
        if (!$asset) {
            throw new BadRequestHttpException(Craft::t('app', 'The Asset you\'re trying to download does not exist.'));
        }

        $this->_requirePermissionByAsset("viewAssetSource", $asset);

        // All systems go, engage hyperdrive! (so PHP doesn't interrupt our stream)
        Craft::$app->getConfig()->maxPowerCaptain();
        $localPath = $asset->getCopyOfFile();

        Craft::$app->getResponse()->sendFile($localPath, $asset->filename, false);
        Io::deleteFile($localPath);
        Craft::$app->end();
    }

    /**
     * Generate a transform.
     *
     * @return Response
     */
    public function actionGenerateTransform()
    {
        $request = Craft::$app->getRequest();
        $transformId = $request->getQueryParam('transformId');
        $returnUrl = (bool)$request->getBodyParam('returnUrl',
            false);

        // If transform Id was not passed in, see if file id and handle were.
        $assetTransforms = Craft::$app->getAssetTransforms();

        if (empty($transformId)) {
            $assetId = $request->getBodyParam('assetId');
            $handle = $request->getBodyParam('handle');
            $assetModel = Craft::$app->getAssets()->getAssetById($assetId);
            $transformIndexModel = $assetTransforms->getTransformIndex($assetModel,
                $handle);
        } else {
            $transformIndexModel = $assetTransforms->getTransformIndexModelById($transformId);
        }

        $url = $assetTransforms->ensureTransformUrlByIndexModel($transformIndexModel);

        if ($returnUrl) {
            return $this->asJson(['url' => $url]);
        }

        return $this->redirect($url);
    }

    /**
     * Require an Assets permissions.
     *
     * @param string $permissionName Name of the permission to require.
     * @param Asset  $asset          Asset on the Volume on which to require the permission.
     *
     * @return void
     */
    private function _requirePermissionByAsset($permissionName, Asset $asset)
    {
        $this->_requirePermissionByVolumeId($permissionName, $asset->volumeId);
    }

    /**
     * Require an Assets permissions.
     *
     * @param string       $permissionName Name of the permission to require.
     * @param VolumeFolder $folder         Folder on the Volume on which to require the permission.
     *
     * @return void
     */
    private function _requirePermissionByFolder($permissionName, VolumeFolder $folder)
    {
        $this->_requirePermissionByVolumeId($permissionName, $folder->volumeId);
    }

    /**
     * Require an Assets permissions.
     *
     * @param string $permissionName Name of the permission to require.
     * @param int    $volumeId       The Volume id on which to require the permission.
     *
     * @return void
     */
    private function _requirePermissionByVolumeId($permissionName, $volumeId)
    {
        $this->requirePermission($permissionName.':'.$volumeId);
    }
}

