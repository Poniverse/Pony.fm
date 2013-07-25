<?php

	abstract class ApiControllerBase extends Controller {
		protected function execute($command) {
			if (!$command->authorize())
				return $this->notAuthorized();

			$result = $command->execute();
			if ($result->didFail()) {
				return Response::json(['message' => 'Validation failed', 'errors' => $result->getValidator()->messages()->getMessages()], 400);
			}

			return Response::json($result->getResponse(), 200);
		}

		public function notAuthorized() {
			return Response::json(['message' => 'You may not do this!'], 403);
		}

		public function notFound($message) {
			return Response::json(['message' => $message], 403);
		}
	}