<?php namespace Entities;

class TrackFile extends \Eloquent {
	public function track() {
		return $this->belongsTo('Entities\Track');
	}
}