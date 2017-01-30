<?php
/**
 * busca-ativa-escolar-api
 * StepTransformer.php
 *
 * Copyright (c) LQDI Digital
 * www.lqdi.net - 2017
 *
 * @author Aryel Tupinambá <aryel.tupinamba@lqdi.net>
 *
 * Created at: 07/01/2017, 23:53
 */

namespace BuscaAtivaEscolar\Transformers;


use BuscaAtivaEscolar\CaseSteps\CaseStep;
use League\Fractal\TransformerAbstract;

class StepTransformer extends TransformerAbstract {

	protected $availableIncludes = [
		'fields',
		'assigned_user',
	];

	protected $defaultIncludes = [
		'assigned_user',
	];

	public function transform(CaseStep $step) {
		$data = [
			'id' => $step->id,
			'child_id' => $step->child_id,
			'case_id' => $step->case_id,
			'order' => $step->order,

			'sequence' => [
				'index' => $step->step_index,
				'next' => ['type' => $step->next_type, 'index' => $step->next_index]
			],

			'name' => $step->getName(),

			'step_type' => $step->step_type,

			'is_completed' => $step->is_completed,
			'is_pending_assignment' => $step->is_pending_assignment,

			'assigned_user_id' => $step->assigned_user_id,
			'assigned_group_id' => $step->assigned_group_id,

			'created_at' => $step->created_at ? $step->created_at->toIso8601String() : null,
			'updated_at' => $step->updated_at ? $step->updated_at->toIso8601String() : null,
			'completed_at' => $step->completed_at ? $step->completed_at->toIso8601String() : null,
		];

		if($step->step_type == 'BuscaAtivaEscolar\\CaseSteps\\Observacao') {
			$data['report_index'] = $step->report_index;
		}

		return $data;
	}

	public function includeFields(CaseStep $step) {
		return $this->item($step, new StepFieldsTransformer, false);
	}

	public function includeAssignedUser(CaseStep $step) {
		if(!$step->assignedUser) return null;
		return $this->item($step->assignedUser, new UserTransformer, false);
	}

}