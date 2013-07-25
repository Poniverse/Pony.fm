<?php
	use Assetic\Asset\BaseAsset;
	use Assetic\Filter\FilterInterface;

	/**
	 * Class CacheBusterAsset
	 * OH GOD IT BUUUUUUURNS
	 *
	 * Well, I may as well tell you why this awful class exists. So... Assetic doesn't quite support less's import
	 * directive. I mean; it supports it insofar as Less itself supports it - but it doesn't take into account the
	 * last modified time for imported assets. Since we only have one less file that imports everything else... well
	 * you can see where this is going. This asset will let us override the last modified time for an entire collection
	 * which allows me to write a custom mechanism for cache busting.
	 */
	class CacheBusterAsset extends BaseAsset {
		private $_lastModified;

		/**
		 * @param int $lastModified
		 */
		public function __construct($lastModified) {
			$this->_lastModified = $lastModified;
			parent::__construct([], '', '', []);
		}

		public function load(FilterInterface $additionalFilter = null) {
		}

		public function getLastModified() {
			return $this->_lastModified;
		}
	}
