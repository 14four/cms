<?php
namespace Craft;

/**
 *
 */
class FileCache extends \CFileCache
{
	/**
	 * Stores a value identified by a key into cache.
	 * If the cache already contains such a key, the existing value and expiration time will be replaced with the new ones.
	 *
	 * @param  string $id                   the key identifying the value to be cached
	 * @param  mixed            $value      the value to be cached
	 * @param  integer          $expire     the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param  ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @return boolean                      true if the value is successfully stored into cache, false otherwise
	 */
	public function set($id, $value, $expire = null, $dependency = null)
	{
		Craft::trace('Saving "'.$id.'" to cache', 'system.caching.'.get_class($this));

		if ($expire === null)
		{
			$expire = craft()->config->getCacheDuration();
		}

		if ($dependency !== null && $this->serializer !== false)
		{
			$dependency->evaluateDependency();
		}

		if ($this->serializer === null)
		{
			$value = serialize(array($value, $dependency));
		}
		elseif ($this->serializer !== false)
		{
			$value = call_user_func($this->serializer[0], array($value, $dependency));
		}

		return $this->setValue($this->generateUniqueKey($id), $value, $expire);
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * Nothing will be done if the cache already contains the key.
	 *
	 * @param  string           $id         the key identifying the value to be cached
	 * @param  mixed            $value      the value to be cached
	 * @param  integer          $expire     the number of seconds in which the cached value will expire. 0 means never expire.
	 * @param  ICacheDependency $dependency dependency of the cached item. If the dependency changes, the item is labeled invalid.
	 * @return boolean                      true if the value is successfully stored into cache, false otherwise
	 */
	public function add($id, $value, $expire = null, $dependency = null)
	{
		Craft::trace('Adding "'.$id.'" to cache', 'system.caching.'.get_class($this));

		if ($expire === null)
		{
			$expire = craft()->config->getCacheDuration();
		}

		if ($dependency !== null && $this->serializer !== false)
		{
			$dependency->evaluateDependency();
		}

		if ($this->serializer === null)
		{
			$value = serialize(array($value,$dependency));
		}
		elseif ($this->serializer !== false)
		{
			$value = call_user_func($this->serializer[0], array($value, $dependency));
		}

		return $this->addValue($this->generateUniqueKey($id), $value, $expire);
	}
}
