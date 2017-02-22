<?php
/**
 * busca-ativa-escolar-api
 * TenantSignUpController.php
 *
 * Copyright (c) LQDI Digital
 * www.lqdi.net - 2016
 *
 * @author Aryel Tupinambá <aryel.tupinamba@lqdi.net>
 *
 * Created at: 22/12/2016, 21:09
 */

namespace BuscaAtivaEscolar\Http\Controllers\Tenants;


use Auth;
use BuscaAtivaEscolar\City;
use BuscaAtivaEscolar\Http\Controllers\BaseController;
use BuscaAtivaEscolar\SignUp;
use BuscaAtivaEscolar\Tenant;

class SignUpController extends BaseController  {

	public function register() {
		$data = request()->all();

		$city = City::find($data['city_id']);

		if(!$city) return $this->failure('invalid_city');

		$existingTenant = Tenant::where('city_id', $city->id)->first();
		$existingSignUp = SignUp::where('city_id', $city->id)->first();

		if($existingTenant) return $this->failure('tenant_already_registered');
		if($existingSignUp) return $this->failure('signup_in_progress');

		try {

			$validator = SignUp::validate($data);

			if($validator->fails()) {
				return $this->failure('invalid_input', $validator->failed());
			}

			$signup = SignUp::createFromForm($data);

			return response()->json(['status' => 'ok', 'signup_id' => $signup->id]);
		} catch (\Exception $ex) {
			return $this->api_exception($ex);
		}

	}

	protected function failure($reason, $fields = null) {
		$data = ['status' => 'error', 'reason' => $reason];
		if($fields) $data['fields'] = $fields;
		return response()->json($data);
	}

	public function get_pending() {
		$pending = SignUp::with('city')->orderBy('created_at', 'ASC')->get();
		return response()->json(['data' => $pending]);
	}

	public function approve(SignUp $signup) {
		try {

			if(!$signup) return $this->failure('invalid_signup_id');

			$signup->approve(Auth::user());
			return response()->json(['status' => 'ok', 'signup_id' => $signup->id]);

		} catch (\Exception $ex) {
			return $this->api_exception($ex);
		}
	}

	public function reject(SignUp $signup) {
		try {

			if(!$signup) return $this->failure('invalid_signup_id');

			$signup->reject(Auth::user());
			return response()->json(['status' => 'ok', 'signup_id' => $signup->id]);

		} catch (\Exception $ex) {
			return $this->api_exception($ex);
		}
	}

	public function resendNotification(SignUp $signup) {
		try {

			if(!$signup) return $this->failure('invalid_signup_id');

			$signup->sendNotification();
			return response()->json(['status' => 'ok', 'signup_id' => $signup->id]);

		} catch (\Exception $ex) {
			return $this->api_exception($ex);
		}
	}

}