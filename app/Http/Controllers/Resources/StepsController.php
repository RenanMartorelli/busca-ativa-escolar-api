<?php
/**
 * busca-ativa-escolar-api
 * StepsController.php
 *
 * Copyright (c) LQDI Digital
 * www.lqdi.net - 2017
 *
 * @author Aryel Tupinambá <aryel.tupinamba@lqdi.net>
 *
 * Created at: 08/01/2017, 01:29
 */

namespace BuscaAtivaEscolar\Http\Controllers\Resources;


use BuscaAtivaEscolar\CaseSteps\CaseStep;
use BuscaAtivaEscolar\Http\Controllers\BaseController;
use BuscaAtivaEscolar\Serializers\SimpleArraySerializer;
use BuscaAtivaEscolar\Transformers\StepTransformer;
use BuscaAtivaEscolar\Transformers\UserTransformer;
use BuscaAtivaEscolar\User;

class StepsController extends BaseController {

	public function show($step_type, $step_id) {

		try {
			$step = CaseStep::fetch($step_type, $step_id);

			return fractal()
				->item($step)
				->transformWith(new StepTransformer())
				->serializeWith(new SimpleArraySerializer())
				->parseIncludes(request('with'))
				->respond();

		} catch (\Exception $ex) {
			return response()->json(['status' => 'error', 'reason' => $ex->getMessage()]);
		}
	}

	public function update($step_type, $step_id) {
		try {

			$data = request()->all();

			$step = CaseStep::fetch($step_type, $step_id);
			$validation = $step->validate($data);

			if($validation->fails()) {
				return response()->json(['status' => 'error', 'reason' => 'validation_failed', 'fields' => $validation->failed()]);
			}

			$input = $step->setFields($data);

			return response()->json(['status' => 'ok', 'updated' => $input]);

		} catch (\Exception $ex) {
			return response()->json(['status' => 'error', 'reason' => $ex->getMessage()]);
		}
	}

	public function complete($step_type, $step_id) {

		try {

			$step = CaseStep::fetch($step_type, $step_id);

			if($step->is_completed) return response()->json(['status' => 'error', 'reason' => 'step_already_completed']);
			if(!$step->assigned_user_id) return response()->json(['status' => 'error', 'reason' => 'no_assigned_user']);

			$validation = $step->validate($step->toArray(), true);

			if($validation->fails()) {
				return response()->json(['status' => 'error', 'reason' => 'validation_failed', 'fields' => $validation->failed()]);
			}

			$next = $step->complete();

			// TODO: $step->canBeCompletedBy(Auth::user());

			if(!$next) return response()->json(['status' => 'ok', 'hasNext' => false]);

			return response()->json([
				'status' => 'ok',
				'hasNext' => true,
				'nextStep' => fractal()
					->item($next)
					->transformWith(new StepTransformer())
					->serializeWith(new SimpleArraySerializer())
			]);

		} catch (\Exception $ex) {
			return response()->json(['status' => 'error', 'reason' => 'exception', 'exception' => $ex->getMessage()]);
		}

	}

	public function getAssignableUsers($step_type, $step_id) {
		try {
			$step = CaseStep::fetch($step_type, $step_id);
			$users = User::query()->where($step->getAssignableUsersFilter())->get();

			return fractal()
				->collection($users, new UserTransformer(), 'users')
				->serializeWith(new SimpleArraySerializer())
				->respond();

		} catch (\Exception $ex) {
			return response()->json(['status' => 'error', 'reason' => 'exception', 'exception' => $ex->getMessage()]);
		}
	}

	public function assignUser($step_type, $step_id) {
		try {
			$user = User::findOrFail(request('user_id'));
			$step = CaseStep::fetch($step_type, $step_id);

			$step->assignToUser($user);

			return response()->json(['status' => 'ok', 'user' => fractal()->item($user, new UserTransformer())]);

		} catch (\Exception $ex) {
			return response()->json(['status' => 'error', 'reason' => 'exception', 'exception' => $ex->getMessage()]);
		}
	}

}