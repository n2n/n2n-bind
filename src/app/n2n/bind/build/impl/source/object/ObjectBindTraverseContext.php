<?php
namespace n2n\bind\build\impl\source\object;

use n2n\util\type\attrs\AttributePath;

class ObjectBindTraverseContext {
	private array $traversedSegments = [];
	private array $remainingSegments;

	/**
	 * @param AttributePath $fullPath The complete attribute path.
	 */
	public function __construct(AttributePath $fullPath, private bool $mustExist) {
		$this->remainingSegments = $fullPath->toArray();
	}

	/**
	 * Shifts one segment from the remaining segments into the traversed segments.
	 * Returns null if no segment remains.
	 */
	public function shiftSegment(): ?string {
		if (empty($this->remainingSegments)) {
			return null;
		}
		$segment = array_shift($this->remainingSegments);
		$this->traversedSegments[] = $segment;
		return $segment;
	}

	/**
	 * Returns the already traversed segments as a string joined with '/'.
	 */
	public function getTraversedPath(): string {
		return implode('/', $this->traversedSegments);
	}

	/**
	 * Returns an AttributePath built from the segments that remain to be traversed.
	 */
	public function getRemainingPath(): AttributePath {
		return new AttributePath($this->remainingSegments);
	}

	public function mustExist(): bool {
		return $this->mustExist;
	}
}