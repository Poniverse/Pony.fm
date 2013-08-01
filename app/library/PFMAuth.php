<?php
	use Illuminate\Auth\EloquentUserProvider;
	use Illuminate\Auth\UserInterface;

	class PFMAuth extends EloquentUserProvider {

		function __construct() {
			parent::__construct(new IpsHasher(), 'Entities\User');
		}

		public function validateCredentials(UserInterface $user, array $credentials) {
			$plain = $credentials['password'];
			return $this->hasher->check($plain, $user->getAuthPassword(), ['salt' => $user->password_salt]);
		}
	}