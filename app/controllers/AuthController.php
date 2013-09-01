<?php

	use Entities\User;

	class AuthController extends Controller {
		protected $poniverse;

		public function __construct() {
			$this->poniverse = new Poniverse(Config::get('poniverse.client_id'), Config::get('poniverse.secret'));
		}

		public function getLogin() {
			if (Auth::guest())
				return Redirect::to($this->poniverse->getAuthenticationUrl('login'));

			return Redirect::to('/');
		}

		public function postLogout() {
			Auth::logout();
			return Redirect::to('/');
		}

		public function getOAuth() {
			$code = $this->poniverse->getClient()->getAccessToken(
				Config::get('poniverse.urls')['token'],
				'authorization_code',
				[
					'code' => Input::query('code'),
					'redirect_uri' => URL::to('/auth/oauth')
				]);

			if($code['code'] != 200) {
				if($code['code'] == 400 && $code['result']['error_description'] == 'The authorization code has expired' && !isset($this->request['login_attempt'])) {
					return Redirect::to($this->poniverse->getAuthenticationUrl('login_attempt'));
				}

				return Redirect::to('/')->with('message', 'Unfortunately we are having problems attempting to log you in at the moment. Please try again at a later time.' );
			}

			$this->poniverse->setAccessToken($code['result']['access_token']);
			$poniverseUser = $this->poniverse->getUser();
			$token = DB::table('oauth2_tokens')->where('external_user_id', '=', $poniverseUser['id'])->where('service', '=', 'poniverse')->first();

			$setData = [
				'access_token'  => $code['result']['access_token'],
				'expires'       => date( 'Y-m-d H:i:s', strtotime("+".$code['result']['expires_in']." Seconds", time())),
				'type'          => $code['result']['token_type'],
			];

			if(isset($code['result']['refresh_token']) && !empty($code['result']['refresh_token'])) {
				$setData['refresh_token'] = $code['result']['refresh_token'];
			}

			if($token) {
				//User already exists, update access token and refresh token if provided.
				DB::table('oauth2_tokens')->where('id', '=', $token->id)->update($setData);
				return $this->loginRedirect(User::find($token->user_id));
			}

			//Check by email to see if they already have an account
			$localMember = User::where('email', '=', $poniverseUser['email'])->first();

			if ($localMember) {
				return $this->loginRedirect($localMember);
			}

			$user = new User;

			$user->mlpforums_name = $poniverseUser['username'];
			$user->display_name = $poniverseUser['display_name'];
			$user->email = $poniverseUser['email'];
			$user->created_at = gmdate("Y-m-d H:i:s", time());
			$user->uses_gravatar = 1;

			$user->save();

			//We need to insert a new token row :O

			$setData['user_id'] = $user->id;
			$setData['external_user_id'] = $poniverseUser['id'];
			$setData['service'] = 'poniverse';

			DB::table('oauth2_tokens')->insert($setData);

			return $this->loginRedirect($user);
		}

		protected function loginRedirect($user, $rememberMe = true) {
			Auth::login($user, $rememberMe);
			return Redirect::to('/');
		}
	}