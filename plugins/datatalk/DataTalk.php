<?php


class DataTalk {

	/**
	 * function to route the request to the appropriate function.
	 */
	public static function route() {
		// if the api key is not set, redirect to settings page and exit
		self::checkChatGptApiKey();

		// if this is an ajax request, serve it and exit
		self::handleAjax();

		// if user is requesting the settings page, show it and exit
		if($_GET['page'] == 'settings') self::showSettingsPage();

		// otherwise, show the page
		self::showQuestionPage();
	}

	/**
	 * function to handle ajax requests, routing them to the appropriate function
	 * based on the purpose of the request, as set in the `purpose` POST variable.
	 * if the purpose is not recognized, the function will exit with a 400 status code.
	 * 
	 * @return void
	 */
	private static function handleAjax() {
		if(!is_ajax()) return;

		// get the purpose
		$purpose = $_POST['purpose'];

		// handle the purpose
		switch($purpose) {
			case 'save-settings':
				self::saveSettings();
				break;
			case 'get-answer':
				self::handleUserQuestion();
				break;
			case 'delete-question':
				self::handleDeleteQuestion();
				break;
		}

		// if we're here, the purpose was not recognized
		http_response_code(400);
		echo 'Invalid purpose.';
		exit;
	}

	/**
	 * controller function to handle deleting a question from the database.
	 * The question id is expected to be set in the `id` POST variable.
	 * On success, the function will exit with a 200 status code.
	 * On error, the function will exit with a 500 status code.
	 */
	private static function handleDeleteQuestion() {
		// get the question id
		$questionId = $_POST['id'];

		// delete the question
		$success = self::deleteQuestion($questionId);

		// on error set the http response code to 500
		if(!$success) http_response_code(500);

		exit;
	}

	/**
	 * controller function to handle user questions.
	 * The question is expected to be set in the `question` POST variable.
	 * The question id is expected to be set in the `id` POST variable, if it's already in the database.
	 * 
	 * The function will get the SQL query corresponding to the question from the database or from the chatgpt api.
	 * If the question is not in the database, the function will store it in the database.
	 * 
	 * The returned answer will be a json object with the following properties:
	 * - `id`: the question id
	 * - `prompt`: the prompt used to get the answer from the chatgpt api
	 * - `query`: the SQL query corresponding to the question, as returned by the chatgpt api or the database
	 * - `answer`: the query resultset
	 * - `alternativeQuery`: an alternative query, if provided by the chatgpt api
	 * - `tokenUsage`: the number of tokens used to get the answer from the chatgpt api
	 * - `error`: whether there was an error in the query
	 * 
	 * On success, the function will exit with a 200 status code and a json response.
	 * On error, the function will exit with a 500 status code and a json response.
	 */
	private static function handleUserQuestion() {
		// get the question
		$question = $_POST['question'];
		$questionId = $_POST['id'] ?? null; // if question id is set, run cached query

		// send json response
		header('Content-Type: application/json');

		// get the answer
		$reply = self::getAnswer($question, $questionId);

		// store the question and answer in the database if it's not already there
		if(!$questionId && !$reply['id'] && !$reply['error'])
			$reply['id'] = self::storeQuestion(
				$question, 
				$reply['query'], 
				count($reply['answer'] ?? [])
			);

		// if there was an error, set the http response code to 500
		if(!empty($reply['error']))	http_response_code(500);

		// send the answer back to the user
		die(json_encode($reply));
	}

	/**
	 * function to retrieve the reply template.
	 * @return array the reply template. See `handleUserQuestion` for details.
	 */
	private static function replyTemplate() {
		return [
			'id' => null,
			'prompt' => null,
			'answer' => null,
			'query'	=> null,
			'alternativeQuery' => null,
			'tokenUsage' => null,
			'error' => null,
		];
	}

	/**
	 * function to get to convert question info as retrieved from the database
	 * to a reply object (see `handleUserQuestion` for details).
	 * 
	 * @param array $qData the question data as retrieved from the database
	 * @return array the reply object
	 */
	private static function replyFromQuestion($qData) {
		$reply = self::replyTemplate();
		$reply['id'] = $qData['id'];
		$reply['prompt'] = self::preparePrompt($qData['question']);
		$reply['query'] = $qData['query'];
		$reply['error'] = $qData['is_error'];
		return $reply;
	}

	/**
	 * function to send a prompt to the chatgpt api and get the auto-completed SQL query.
	 * 
	 * @param array $prompt the prompt to send to the chatgpt api as an associative array with the following properties:
	 * 					- `system`: the system message
	 * 					- `user`: the user message
	 * @return array the reply object. See `handleUserQuestion` for details.
	 */
	private static function getQueryFromChatGpt($prompt) {
		$apiKey = self::setting('chatgpt_api_key');

		$reply = self::replyTemplate();
		$reply['prompt'] = $prompt;

		// https://platform.openai.com/docs/api-reference/completions
		$ch = curl_init('https://api.openai.com/v1/chat/completions');
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer ' . $apiKey,
				'Content-Type: application/json'
			],
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => json_encode([
				'model' => 'gpt-3.5-turbo',
				'messages' => [
					[
						'role' => 'system',
						'content' => $prompt['system']
					],
					[
						'role' => 'user',
						'content' => $prompt['user']
					],
				],
				'temperature' => 0,
				'max_tokens' => 1000,
				'stop' => ['#', ';'],
				'presence_penalty' => 0.0,
				'frequency_penalty' => 0.0,
				'top_p' => 1
			])
		]);

		// get the response
		$response = curl_exec($ch);
		curl_close($ch);
		if(!$response) return ['error' => curl_error($ch)];

		// decode the response
		$response = json_decode($response, true);
		if(!$response) return ['error' => json_last_error_msg()];

		// get the answer
		$reply['query'] = $response['choices'][0]['message']['content'];
		if(!empty($response['choices'][1])) {
			$reply['alternativeQuery'] = $response['choices'][1]['message']['content'];
		}

		// get the token usage
		$reply['tokenUsage'] = $response['usage'];

		return $reply;
	}

	/**
	 * function to get the answer to a question. If the question is not in the database,
	 * it will be sent to the chatgpt api.
	 * 
	 * @param string $question the question
	 * @param int $questionId the question id, if it's already in the database
	 * @return array the reply. See `handleUserQuestion` for more details.
	 */
	private static function getAnswer($question, $questionId = null) {
		$reply = self::replyTemplate();
		$qData = null;

		// if question id is set and valid, or question already exists, run cached query
		if(
			($questionId && $qData = self::getQuestionById($questionId))
			|| ($qData = self::getQuestion($question))
		) {
			if(!empty($qData)) {
				$reply = self::replyFromQuestion($qData);
				if($reply['error']) return $reply;

				self::executeQuery($reply);
				return $reply;
			}
		}

		$prompt = self::preparePrompt($question);

		$reply = self::getQueryFromChatGpt($prompt);
		if(!empty($reply['error'])) return $reply;

		self::executeQuery($reply);

		return $reply;
	}

	/**
	 * function to execute SQL query and get a resultset.
	 * 
	 * @param string $reply the reply object. See `handleUserQuestion` for details.
	 *                      The `query` and `alternativeQuery` properties will be used.
	 * 					    The `error` property will be set if an error occurs.
	 * 					    The `answer` property will be set to the resultset.
	 */
	private static function executeQuery(&$reply) {
		// query must begin with SELECT
		if(!preg_match('/^\s*SELECT\s+/i', $reply['query'])) {
			$reply['error'] = 'Query must begin with SELECT.';
			return;
		}

		// make sure query is not empty
		if(trim($reply['query']) == 'SELECT') {
			$reply['error'] = 'Query is empty. Please make sure you are <a href="?page=settings">using a valid, non-expired ChatGPT API key</a>.';
			return;
		}

		// TODO: handle pagination?
		$eo = ['silentErrors' => true];
		$res = sql($reply['query'], $eo);
		if(!$res) {
			if(empty($reply['alternativeQuery'])) {
				$reply['error'] = $eo['error'];
				return;
			}

			// try the alternative query
			$res = sql($reply['alternativeQuery'], $eo);
			if(!$res) {
				$reply['error'] = $eo['error'];
				return;
			}
		}

		$dataset = [];
		while($row = db_fetch_assoc($res)) $dataset[] = $row;
		$reply['answer'] = $dataset;
	}

	/**
	 * function to prepare a prompt for the chatgpt api.
	 * 
	 * @param string $question the question
	 * @return array the prompt, an associative array with the following properties:
	 * 					- `system`: the system message
	 * 					- `user`: the user message
	 */
	private static function preparePrompt($question) {
		// get the tables array
		$tables = self::getTables();

		return [
			'system' => implode("\n", [
				'MySQL tables:',
				'',
				'- ' . implode("\n- ", $tables),
				'',
				'Suggest a query with descriptive column aliases to answer user questions using the above tables.',
				'Reply with only a valid SQL SELECT query and no other text.',
				'The output SQL should only include the fields mentioned in the database structure. Do not include any fields not stated in the structure.',
			]),
			'user' => $question,
		];
	}

	/**
	 * function to get the simplified table schema for use in the prompt.
	 * 
	 * @return array the tables array
	 */
	private static function getTables() {
		$tables = getTableList();
		foreach($tables as $tn => $ignore) {
			$fields = get_table_fields($tn); // an array of field names => field properties
			// convert each table to a string: table_name(field1, field2, ...)
			$tables[$tn] = "{$tn}: (" . implode(', ', array_keys($fields)) . ")";
		}

		return array_values($tables);
	}

	/**
	 * function to display the question page elements.
	 */
	private static function showQuestionPage() {
		global $Translation;
		include_once(APP_PATH . 'header.php');

		echo self::pageTitle();
		echo self::baseJS();
		echo self::questionHandlerJS();
		echo self::historyHandlerJS();
		echo self::questionPageComponents();

		include_once(APP_PATH . 'footer.php');
	}

	/**
	 * function to prepare the base JS objects.
	 * 
	 * @return string the base JS code
	 */
	private static function baseJS() {
		ob_start();
		?>
		<script>
			$j(() => {
				AppGini = AppGini || {};
				AppGini.DataTalk = AppGini.DataTalk || {};
			});

			// function to copy the contents of an element to the clipboard
			function copyMe(el) {
				var range = document.createRange();
				range.selectNode(el);
				window.getSelection().removeAllRanges();
				window.getSelection().addRange(range);
				document.execCommand("copy");
				window.getSelection().removeAllRanges();

				// temporarirly replace the element text with "copied",
				// preservint element height
				const $el = $j(el);
				const oldHtml = $el.html();
				const oldHeight = $el.outerHeight();
				$el.html('Copied!');
				$el.outerHeight(oldHeight);
				setTimeout(() => {
					$el.html(oldHtml);
				}, 600);
			}
		</script>
		<?php
		return ob_get_clean();
	}

	private static function questionPageComponents() {
		// wrap components in a css grid
		return '<div id="question-page">' .
			'<div id="question-answer-container">' .
				self::questionForm() . 
				self::answerSection() .
			'</div>' .
			'<div id="question-history-container" style="display: none;">' .
				self::questionsHistory() .
			'</div>' .
		'</div>' .
		// css grid styles
		'<style>
			.question-page-grid-on {
				display: grid;
				grid-template-columns: 2fr 1fr;
				grid-gap: 2em;
			}
			// first grid item should be 2/3 of the page
			.question-page-grid-on > div:first-child {
				grid-column: 1 / 2;
			}
			// second grid item should be 1/3 of the page
			.question-page-grid-on > div:last-child {
				grid-column: 2 / 2;
			}
		</style>';
	}

	private static function questionsHistory() {
		// get the questions history
		$history = self::getRecentQuestions();
		
		// render the questions history in the form of
		// a panel with a list of questions
		ob_start();
		?>
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Questions History</h3>
			</div>
			<ul class="list-group" id="questions-history">
				<!-- empty list item by default till populated -->
				<li class="list-group-item text-center text-italic" id="empty-history-hint">No questions yet</li>
			</ul>
		</div>

		<script>
			$j(() => {
				const history = <?php echo json_encode($history); ?>;

				AppGini.DataTalk.History.init(history);
			});
		</script>
		<?php
		return ob_get_clean();
	}

	private static function historyHandlerJS() {
		global $Translation;
		ob_start();
		?>
			<script>
				$j(() => {
					// append to AppGini.DataTalk
					AppGini.DataTalk = {
						...AppGini.DataTalk,
						History: {
							appendQuestion: (id, question, prepend = false) => {
								// if no id or question, abort
								if(!id || !question) return;

								// if id already exists, abort
								if($j('#questions-history').find(`a[data-id="${id}"]`).length) return;

								// create the list item
								const li = $j('<li class="list-group-item" style="display: grid; grid-template-columns: 1fr 1em 1em; grid-column-gap: 0.5em;"></li>');
								
								// create the link
								const a = $j('<a href="#" class="question-history-item"></a>');
								a.attr('data-id', id);
								a.text(question);
								li.append(a);
								
								// create a refresh link (icon) to the right
								const refreshLink = $j('<a href="#" class="text-muted refresh-question"><i class="glyphicon glyphicon-refresh"></i></a>');
								refreshLink.attr('data-id', id);
								refreshLink.attr('title', 'Re-ask this question to ChatGPT');
								li.append(refreshLink);

								// create a delete link (icon) to the right
								const deleteLink = $j('<a href="#" class="text-muted delete-question"><i class="glyphicon glyphicon-remove"></i></a>');
								deleteLink.attr('data-id', id);
								deleteLink.attr('title', '<?php echo $Translation['delete']; ?>');
								li.append(deleteLink);

								// append/prepend the list item to the list
								if(prepend) $j('#questions-history').prepend(li);
								else $j('#questions-history').append(li);

								// hide the empty history hint
								$j('#empty-history-hint').addClass('hidden');
							},
							prependQuestion: (id, question) => {
								AppGini.DataTalk.History.appendQuestion(id, question, true);
							},
							removeQuestion: (id) => {
								const li = $j('#questions-history').find(`a[data-id="${id}"]`).closest('li');
								console.log(li);

								// if delete link is disabled, abort
								if(li.find('.disabled').length) return;

								// send a request to delete the question from the database
								return $j.ajax({
									url: 'index.php',
									method: 'post',
									data: {
										'purpose': 'delete-question',
										'id': id,
									},
									beforeSend: () => {
										// disable the delete link and replace it with a spinner
										li.find('.delete-question').addClass('disabled');
										li.find('.delete-question .glyphicon').removeClass('glyphicon-remove').addClass('glyphicon-refresh loop-rotate');
									},
									success: () => {
										// remove the question from the list
										li.hide(100, () => {
											li.remove();

											// if no questions left, show the empty history hint
											if($j('#questions-history').find('li').length == 1) {
												$j('#empty-history-hint').removeClass('hidden');
											}
										});
									},
									error: () => {
										// enable the delete link and replace the spinner with the delete icon
										li.find('.delete-question').removeClass('disabled');
										li.find('.delete-question .glyphicon').removeClass('glyphicon-refresh loop-rotate').addClass('glyphicon-remove');
									},
								});
							},
							clear: () => {
								// send a request to delete all questions from the database
								return $j.ajax({
									url: 'index.php',
									method: 'post',
									data: {
										'purpose': 'delete-all-questions',
									},
									success: () => {
										// remove all questions from the list
										$j('#questions-history').empty();
									},
								});
							},
							loadQuestion: (id) => {
								// get the question
								const question = $j('#questions-history').find(`a[data-id="${id}"]`).text();
								// set the question in the question textarea
								$j('#question').val(question);

								$j('#question').focus();
								setTimeout(() => {
									$j('#submit-question').focus();
								}, 400);

							},
							reAskQuestion: (id) => {
								AppGini.DataTalk.History.loadQuestion(id);

								// delete from history to force a new request to ChatGPT
								AppGini.DataTalk.History.removeQuestion(id).then(() => {
									$j('#submit-question').click();
								});
							},
							togglePanel: () => {
								const panelShown = $j('#question-page').hasClass('question-page-grid-on');
								const toggler = $j('.toggle-question-history');

								// disable toggle button
								toggler.prop('disabled', true);

								// store the toggle state in localStorage
								localStorage.setItem('AppGiniPlugin.DataTalk.expandHistoryPanel', panelShown ? '0' : '1');

								if(panelShown) {
									// hide history panel
									$j('#question-history-container').hide(100, () => {
										$j('#question-page').removeClass('question-page-grid-on');
										// enable toggle button
										toggler.prop('disabled', false).removeClass('active');
									});

									return;
								}

								// show the history panel
								$j('#question-history-container').show(100, () => {
									$j('#question-page').addClass('question-page-grid-on');
									// enable toggle button
									toggler.prop('disabled', false).addClass('active');
								});
							},
							init: (history) => {
								// append the questions to the list
								for(let i = 0; i < history.length; i++) {
									const q = history[i];
									AppGini.DataTalk.History.appendQuestion(
										q.id,
										q.question
									);
								}

								// expand history panel if it was expanded before
								if(localStorage.getItem('AppGiniPlugin.DataTalk.expandHistoryPanel') == '1') {
									AppGini.DataTalk.History.togglePanel();
								}
							}
						}
					}

					// handle click on question history item
					$j('#questions-history').on('click', '.question-history-item', function(e) {
						e.preventDefault();
						const id = $j(this).data('id');
						AppGini.DataTalk.History.loadQuestion(id);
					});

					// handle click on delete question history item
					$j('#questions-history').on('click', '.delete-question', function(e) {
						e.preventDefault();
						const id = $j(this).data('id');
						AppGini.DataTalk.History.removeQuestion(id);
					});

					// handle click on refresh question history item
					$j('#questions-history').on('click', '.refresh-question', function(e) {
						e.preventDefault();
						const id = $j(this).data('id');
						AppGini.DataTalk.History.reAskQuestion(id);
					});
				});
			</script>
		<?php

		return ob_get_clean();

	}

	private static function checkChatGptApiKey() {
		self::setupDataTalk();
		
		// if no api key is set and we're in ajax mode, show an error and exit
		$apiKey = self::setting('chatgpt_api_key');
		if(!$apiKey && is_ajax()) {
			// if we're saving settings, skip this check
			if($_POST['purpose'] == 'save-settings') return;

			// response code 500: Internal Server Error
			http_response_code(500);
			echo 'ChatGPT API key is not set. Please set it in the DataTalk settings.';
			exit;
		}

		// if no api key is set and we're not in ajax mode, show settings page
		if(!$apiKey) self::showSettingsPage();
	}

	private static function showSettingsPage() {
		global $Translation;
		include_once(APP_PATH . 'header.php');

		echo self::pageTitle();
		echo self::settingsForm();
		echo self::settingsHandlerJS();

		include_once(APP_PATH . 'footer.php');
		exit;
	}

	private static function settingsForm() {
		$apiKey = self::setting('chatgpt_api_key');

		ob_start();
		?>
			<h2>Settings</h2>
			<form id="settings-form" method="post" action="index.php">
				<div class="form-group">
					<label for="chatgpt_api_key">ChatGPT API key</label>
					<input type="text" class="form-control" id="chatgpt_api_key" value="<?php echo html_attr($apiKey); ?>">
					<!-- add help -->
					<p class="help-block">
						You can <a href="https://platform.openai.com/account/api-keys" target="_blank">create a ChatGPT API key here</a>. 
						ChatGPT API keys have a generous free quota, and <a href="https://openai.com/pricing" target="_blank">very affordable per-usage pricing</a> if free quota is exceeded.
					</p>
				</div>
				<div class="hidden alert alert-danger" id="error-message"></div>

				<button type="button" class="btn btn-default" id="discard-changes">
					<span class="glyphicon glyphicon-remove"></span>
					Discard changes
				</button>
				<span class="hspacer-md"></span>
				<button type="button" class="btn btn-primary" id="submit-settings">
					<span class="glyphicon glyphicon-ok"></span>
					Save settings
				</button>
			</form>
		<?php

		return ob_get_clean();
	}

	private static function settingsHandlerJS() {
		ob_start();
		?>
		<script>
			$j(() => {
				$j('#submit-settings').click(() => {
					// hide error message
					$j('#error-message').addClass('hidden');

					// disable the submit button
					$j('#submit-settings').prop('disabled', true);

					// send settings to the server
					$j.ajax({
						url: 'index.php',
						type: 'post',
						data: {
							purpose: 'save-settings',
							chatgpt_api_key: $j('#chatgpt_api_key').val()
						},
						success: () => {
							// reload the page
							window.location.reload();
						},
						error: (xhr, status, error) => {
							// show the error message
							$j('#error-message').removeClass('hidden').html(xhr.responseText);

							// enable the submit button
							$j('#submit-settings').prop('disabled', false);
						}
					})
				})
				$j('#discard-changes').click(() => {
					// redirect to the main page
					window.location = 'index.php';
				})
				
				// no need for the question history toggle button here
				$j('.toggle-question-history').remove();
			})
		</script>
		<?php
		return ob_get_clean();
	}

	private static function saveSettings() {
		// get the settings
		$apiKey = $_POST['chatgpt_api_key'];

		// save the settings
		self::setting('chatgpt_api_key', $apiKey);

		// if we're here, the settings were saved successfully
		echo 'Settings saved successfully.';
		exit;
	}

	private static function pageTitle() {
		global $Translation;
		ob_start();
		?>
			<h1>
				<a href="index.php"><img src="datatalk-logo.png" alt="DataTalk" style="height: 1.25em;"></a>
			</h1>
			<h4>
				Ask natural language questions about your data and get answers, powered by 
				<a target="_blank" href="https://chat.openai.com/">ChatGPT</a>

				<span class="pull-right">
					<a href="index.php?page=settings" class="btn btn-default btn-sm">
						<span class="glyphicon glyphicon-cog"></span>
						Settings
					</a>
					<a href="#" class="btn btn-default btn-sm lspacer-md toggle-question-history" onclick="AppGini.DataTalk.History.togglePanel(); return false;">
						<span class="glyphicon glyphicon-time"></span>
						History
					</a>
					<a href="#" class="btn btn-default btn-sm lspacer-md data-safety-note" onclick="$j('#data-safety-note-modal').modal('show'); return false;">
						<span class="glyphicon glyphicon-info-sign"></span>
						Data safety
					</a>
				</span>
			</h4>
			<hr/>

			<!-- data safety note modal -->
			<div class="modal fade" id="data-safety-note-modal" tabindex="-1" role="dialog">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							<h4 class="modal-title" id="data-safety-note-modal-label">Data safety</h4>
						</div>
						<div class="modal-body">
							<h4>How does DataTalk work and what data does it send to ChatGPT?</h4>
							<ul style="line-height: 2em;">
								<li> DataTalk is powered by ChatGPT, a third-party service 
									that is not affiliated with DataTalk, AppGini, or BigProf Software. </li>
								<li> When you ask a question, DataTalk prepares a "prompt" consisting of your question and the simplified schema of the database (see the example prompt at the bottom of this popup). </li>
								<li class="text-success"> The prompt doesn't include any data from your database. </li>
								<li> The prompt is sent to the ChatGPT API. </li>
								<li class="text-success"> ChatGPT doesn't have access to your database or the data stored in it. </li>
								<li> ChatGPT doesn't know any information about the user who asked the question. </li>
								<li> ChatGPT uses the prompt to generate an SQL query to answer your question. </li>
								<li> The SQL query is a <code>SELECT</code> query that doesn't modify the database. </li>
								<li> ChatGPT may use your question and the query it generates for its own purposes, including but not limited to 
									developing and improving its service.</li>
								<li> You can read more about ChatGPT's privacy policy <a target="_blank" href="https://openai.com/policies/privacy-policy">here</a>. </li>
							</ul>

							<!-- show more details -->
							<a class="btn btn-default btn-sm btn-block" data-toggle="collapse" href="#data-safety-note-modal-details">
								An example prompt sent to ChatGPT to answer the question "Top 10 customers"
							</a>
							<!-- collapsed by default -->
							<div class="collapse" id="data-safety-note-modal-details">
								<pre style="margin-top: 1em;"><?php print_r(self::preparePrompt('Top 10 customers')); ?></pre>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
						</div>
					</div>
				</div>
			</div>
		<?php

		return ob_get_clean();
	}	

	private static function questionForm() {
		global $Translation;
		ob_start();
		?>
			<form id="question-form" method="post" action="index.php">
				<div class="form-group">
					<div style="display: flex; align-items: flex-end; justify-content: space-between;">
						<label for="question">Question</label>

						<div class="btn-group btn-group-sm always-shown-inline-block">
							<button type="button" class="btn btn-default clear-question clear-answer">
								<span class="glyphicon glyphicon-remove"></span>
								Clear all
							</button>
							<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
								<span class="caret"></span>
								<span class="sr-only">Toggle Dropdown</span>
							</button>
							<ul class="dropdown-menu">
								<li><a href="#" class="clear-question"><span class="glyphicon glyphicon-remove"></span> Clear question</a></li>
								<li><a href="#" class="clear-answer"><span class="glyphicon glyphicon-remove"></span> Clear answer</a></li>
							</ul>
						</div>
					</div>
					<textarea class="form-control tspacer-md" id="question" rows="3" placeholder="Give me a list of customers from New York who placed orders within the last 3 months"></textarea>
				</div>
				<div class="hidden alert alert-danger" id="error-message"></div>

				<button type="button" class="btn btn-primary" id="submit-question">
					<span class="glyphicon glyphicon-search"></span>
					Find answers
				</button>
			</form>
			<div class="vspacer-lg"></div>
		<?php

		return ob_get_clean();
	}

	private static function questionHandlerJS() {
		global $Translation;
		ob_start();
		?>
			<script>
				$j(() => {
					AppGini.DataTalk = {
						...AppGini.DataTalk,
						Question: {
							plainText: (question) => {
								// create hidden div via jquery to hold the question text
								let div = $j('<div></div>');
								div.html(question);
								const text = div.text().trim();
								div.remove();
								return text;
							},
							getAnswer: (id) => {
								const question = AppGini.DataTalk.Question.plainText($j('#question').val());

								// hide the error message
								$j('#error-message').addClass('hidden');

								// if the question is empty, focus it, show an error message and exit
								if(!question) {
									$j('#question').focus();
									$j('#error-message').removeClass('hidden').html('Please enter a question.');
									return;
								}

								// disable the submit button and question textarea
								$j('#submit-question').prop('disabled', true);
								$j('#question').prop('disabled', true);
								$j('#answer').html('<span class="glyphicon glyphicon-refresh loop-rotate"></span> Loading ...');

								// send the question to the server
								$j.ajax({
									url: 'index.php',
									type: 'post',
									data: { 
										purpose: 'get-answer',
										question,
										id,
									},
									success: function(resp) {
										// show the answer formatted as a table
										AppGini.DataTalk.Question.answerToTable(resp.answer, question, resp.query);
										console.log(resp);

										// prepend the question to the question history
										AppGini.DataTalk.History.prependQuestion(resp.id, question);
									},
									error: function(e) {
										// do we have a response json that contains an error message?
										if(e.responseJSON && e.responseJSON.error) {
											// show the error message
											$j('#error-message')
												.removeClass('hidden')
												.html(`
													<b>An error occurred while processing your question</b>
													${e.responseJSON.error.length > 1 ? `<br/><br/>${e.responseJSON.error}` : ''}
												`);
											$j('#answer').html('');
											return;
										}

										// show an error message
										$j('#error-message')
											.removeClass('hidden')
											.html(`
												<b>An error occurred while processing your question. Please try again.</b>
												<br/><br/>
												${e.responseText}
											`);
									},
									complete: function() {
										// enable the submit button and question textarea
										$j('#submit-question').prop('disabled', false);
										$j('#question').prop('disabled', false);
									}
								});
							},

							answerToTable: (answer, question, sql) => {
								// if answer is empty or not an array, return empty string
								if(!answer || !Array.isArray(answer) || !answer.length) {
									$j('#answer').html(`
										No answer found. Please try rephrasing the question, or adding a hint on which tables and/or fields to use.
										${sql ? `<br/><br/><pre>${sql}</pre>` : ''}
									`);
									return;
								}

								// get the column names from the first row
								const columns = Object.keys(answer[0]);

								// question to slug, max 50 chars
								const slug = question.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-').replace(/^-/g, '').substr(0, 50).replace(/-$/, '');

								// create the answer title, with a download button, and a button to toggle the SQL query
								let html = `
									<h3 class="bspacer-lg">
										${question}
										<span class="pull-right">
											<a class="btn btn-sm btn-info hspacer-md" data-toggle="collapse" href="#sql-query">
												<span class="glyphicon glyphicon-console"></span>
												Show SQL
											</a>
											<a href="#" onclick="AppGini.DataTalk.Question.exportTableToCSV('${slug}.csv')" class="download-answer-csv btn btn-sm btn-info">
												<span class="glyphicon glyphicon-download-alt"></span>
												Download as CSV
											</a>
										</span>
									</h3>`;

								// create the SQL query div
								html += `<pre class="collapse" id="sql-query" style="cursor: pointer;" title="Click to copy" onclick="copyMe(this)">${sql}</pre>`;

								// create the table header
								html += '<div class="table-responsive">';
								html += '<table class="table table-striped table-bordered table-hover table-condensed">';
								html += '<thead><tr>';
								html += '<th>#</th>';
								for(let i = 0; i < columns.length; i++) {
									html += '<th>' + columns[i] + '</th>';
								}
								html += '</tr></thead>';

								// create the table body
								html += '<tbody>';
								for(let i = 0; i < answer.length; i++) {
									html += '<tr>';
									html += `<td>${i + 1}</td>`;
									for(let j = 0; j < columns.length; j++) {
										html += `<td>${answer[i][columns[j]] ?? ''}</td>`;
									}
									html += '</tr>';
								}
								html += '</tbody>';

								// close the table
								html += '</table>';
								html += '</div>';

								// show the answer
								$j('#answer').html(html);
							},

							exportTableToCSV: (csvFilename) => {
								const csvField = (val) => `"${(val?.toString() || '').replace('"', '""')}"`;

								// get the table
								const $table = $j('#answer table');

								// get the table headers
								const headers = $table.find('thead th').map((i, th) => csvField($j(th).text())).get().join(',');

								// get the table rows
								const rows = $table.find('tbody tr').map((i, tr) => {
									return $j(tr).find('td').map((i, td) => csvField($j(td).text())).get().join(',');
								}).get();

								// create the CSV file
								const csv = new Blob(["\ufeff", [headers, ...rows].join('\n')], { type: 'text/csv;charset=utf-8;' });

								// create a temp link to download the CSV file
								const downloadLink = document.createElement('a');
								downloadLink.download = csvFilename;
								downloadLink.href = window.URL.createObjectURL(csv);
								downloadLink.style.display = 'none';
								document.body.appendChild(downloadLink);
								downloadLink.click();
								downloadLink.remove();

								// return false to prevent default link behavior
								return false;
							},
						},
					}

					$j('#submit-question').click(() => {
						AppGini.DataTalk.Question.getAnswer();
					});
					
					$j('.clear-question').click((e) => {
						e.preventDefault();
						$j('#question').val('').focus();
						$j('#error-message').addClass('hidden');
					});
					
					$j('.clear-answer').click((e) => {
						e.preventDefault();
						$j('#answer').html('');
						$j('#question').focus();
						$j('#error-message').addClass('hidden');
					});
				});
			</script>
		<?php

		return ob_get_clean();
	}

	private static function answerSection() {
		global $Translation;
		ob_start();
		?>
			<div id="answer"></div>
		<?php

		return ob_get_clean();
	}

	private static function setupDataTalk() {
		$eo = ['silentErrors' => true, 'noErrorQueryLog' => true];
		
		// create datatalk_settings table if it doesn't exist
		sql("CREATE TABLE IF NOT EXISTS `appgini_datatalk_settings` (
			`name` varchar(50) NOT NULL,
			`value` text,
			PRIMARY KEY (`name`)
		)", $eo);

		// create datatalk_questions table if it doesn't exist
		// this table will be used to store the questions asked by users,
		// and the queries returned by the ChatGPT API
		sql("CREATE TABLE IF NOT EXISTS `appgini_datatalk_questions` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`question` text NOT NULL,
			`query` text NOT NULL,
			`date` datetime NOT NULL,
			`memberID` varchar(100) NOT NULL,
			`results_count` int(11) NOT NULL DEFAULT '0',
			`is_error` tinyint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (`id`),
			KEY `memberID` (`memberID`)
		) COLLATE='utf8mb4_unicode_ci'", $eo);
	}

	private static function setting($name, $value = null) {
		// if value is provided, update the setting,
		// in all cases, return the setting value
		if($value !== null) {
			$eo = ['silentErrors' => true, 'noErrorQueryLog' => true];
			sql("REPLACE INTO `appgini_datatalk_settings` SET `name`='" . makeSafe($name) . "', `value`='" . makeSafe($value) . "'", $eo);
		}

		// return the setting value
		$value = sqlValue("SELECT `value` FROM `appgini_datatalk_settings` WHERE `name`='" . makeSafe($name) . "'");
		return trim($value);
	}

	/**
	 * function to store a question and its query in the database
	 * 
	 * @param string $question the question asked by the user
	 * @param string $query the query returned by the ChatGPT API
	 * @param int $results_count the number of results returned by the query
	 * @param bool $is_error whether the query is an error or not
	 * @return int|bool the id of the newly inserted question on success, false on failure
	 */
	private static function storeQuestion($question, $query, $results_count = 0, $is_error = false) {
		$memberID = getLoggedMemberID();
		$eo = ['silentErrors' => true];
		$res = sql("INSERT INTO `appgini_datatalk_questions` SET 
			`question`='" . makeSafe(trim($question)) . "', 
			`query`='" . makeSafe(trim($query)) . "', 
			`results_count`='" . intval($results_count) . "',
			`is_error`='" . ($is_error ? '1' : '0') . "',
			`date`='" . date('Y-m-d H:i:s') . "', 
			`memberID`='" . makeSafe($memberID) . "'", $eo);

			// return the id of the newly inserted question on success, false on failure
		return $res ? db_insert_id() : false;
	}

	/**
	 * function to retrieve current user's most recent n questions
	 * 
	 * @param int $n the number of questions to retrieve. default is 100.
	 * @return array an array of questions
	 */
	private static function getRecentQuestions($n = 100) {
		$eo = ['silentErrors' => true];
		$memberID = getLoggedMemberID();
		$res = sql("SELECT * FROM `appgini_datatalk_questions` WHERE `memberID`='" . makeSafe($memberID) . "' ORDER BY `date` DESC LIMIT " . intval($n), $eo);
		$questions = [];
		while($row = db_fetch_assoc($res)) {
			$questions[] = $row;
		}

		return $questions;
	}

	/**
	 * function to retrieve a question from current user's questions given its id
	 * 
	 * @param int $id the id of the question to retrieve
	 * @return array|bool the question record on success, false on failure
	 */
	private static function getQuestionById($id) {
		$eo = ['silentErrors' => true];
		$memberID = getLoggedMemberID();
		$res = sql("SELECT * FROM `appgini_datatalk_questions` WHERE `memberID`='" . makeSafe($memberID) . "' AND `id`='" . intval($id) . "' LIMIT 1", $eo);
		return $res ? db_fetch_assoc($res) : false;
	}

	/**
	 * function to retrieve a question from current user's questions
	 * 
	 * @param string $question the question to search for
	 * @return array|bool the question record on success, false on failure
	 */
	private static function getQuestion($question) {
		$eo = ['silentErrors' => true];
		$memberID = getLoggedMemberID();
		$res = sql("SELECT * FROM `appgini_datatalk_questions` WHERE `memberID`='" . makeSafe($memberID) . "' AND `question` LIKE '" . trim(makeSafe($question)) . "' LIMIT 1", $eo);
		return $res ? db_fetch_assoc($res) : false;
	}

	/**
	 * function to delete a question from the database
	 * 
	 * @param int $id the id of the question to delete
	 * @return bool true on success, false on failure
	 */
	private static function deleteQuestion($id) {
		$eo = ['silentErrors' => true];
		$memberID = getLoggedMemberID();
		$res = sql("DELETE FROM `appgini_datatalk_questions` WHERE `memberID`='" . makeSafe($memberID) . "' AND `id`='" . intval($id) . "' LIMIT 1", $eo);
		return $res ? true : false;
	}

	/**
	 * function to retrieve the query of a question from the database
	 * if the question is not found in the database, return false
	 * 
	 * @param string $question the question to retrieve the query for
	 * @return string|false
	 */
	private static function getCachedQuery($question) {
		$eo = ['silentErrors' => true];
		$query = sqlValue("SELECT `query` FROM `appgini_datatalk_questions` WHERE `question` LIKE '" . makeSafe(trim($question)) . "' ORDER BY `date` DESC LIMIT 1", $eo);
		return $query;
	}
}