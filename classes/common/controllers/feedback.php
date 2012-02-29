<?php
AutoLoad::path(dirname(__FILE__) . '/controller.php');
AutoLoad::path(dirname(__FILE__) . '/../widgets/form.php');
AutoLoad::path(dirname(__FILE__) , '/../utils/utils.php');

Utils::define('MAIL_FEEDBACK', 'feedback@mobi-money.ru');
Utils::define('MAIL_FEEDBACK_FROM', 'robot@mobi-money.ru');

class FeedBack extends Controller {
	
	public function form() {
		$form = $this->getForm();

		if ($form->submit($_POST)) {	
			try {
				$preview = substr($form->message, 0, 200) . "..";
				$subject = mb_encode_mimeheader($preview, "utf-8", "B", "");
				
				$headers =  "From: " . MAIL_FEEDBACK_FROM . "\r\n".
							"To: " . MAIL_FEEDBACK . "\r\n".
							"MIME-Version: 1.0\r\n".
							"Content-type: text/html; charset=utf-8\r\n";
				
   				if ($form->name->get()) {
					$name = "Имя отправителя: ".$form->name->get();
				} else {
					$name="Имя отправителя не указано";
				}
   					
   				if ($form->email->get()) {
					$email = "E-mail отправителя: <a href=\"mailto:{$form->email->get()}\">{$form->email->get()}</a>";
				} else {
					$email = "E-mail отправителя не указан";
				}
   				
				$phone = "";
   				if ($form->phone->get()) {   					   					
   					$phone = "Телефон отправителя: " . $form->phone->get();
   				} 
				
				$client = "";
				if ($form->client->get()) {
					$client = "От клиента {$form->client->get()}";
				} else {
   					$client = "От неавторизованного пользователя"; 
   				}
   				
				$formMessage = preg_replace('/[\n]/i','<br/>',$form->message);

				$message = <<<MSG
					<p>
						@issuetype={$form->typemsg}
						Тип платежа: {$form->typepay}
					</p>
					<p>
						{$name}<br/>
						{$email}<br/>
						Письмо было отправлено с IP: {$_SERVER['REMOTE_ADDR']}<br/>
						{$phone}<br/>
						{$client}
					</p>
					<p>
						{$formMessage}
					</p>
MSG;
			
				if ($form->info->get()) {
					$message .= '<br/>' . $form->info->get();
				}
			
				$message = "Письмо пришло с {$_SERVER['HTTP_HOST']}<br/>{$message}";
				
				Utils::mail(MAIL_FEEDBACK, $subject, $message);
				
				$this->success = new SuccessBox(__('FEEDBACK_MESSAGE_SENT_SUCCESS')); //Ваше сообщение отправлено
				$form = $this->getForm();
			} catch (Exception  $e) {		
				$this->error = new ErrorBox(__('FEEDBACK_MESSAGE_SENT_ERROR')); //Ошибка отправки почты попробуйте позднее
			}			
		}		
		
		$this->form = $form;
	}

	protected function getForm() {
		$messageTypes = array(
			'Проблема' => __('feedback_request_problem'), //Проблемы с платежом
			'Консультация' => __('feedback_request_ask'), //Подскажите мне...
			'Улучшение' => __('feedback_request_suggestion'), //Я знаю как улучшить ваш сервис!
			'Работа' => __('feedback_request_work') //Хотим с вами работать!
		);
		$payTypes = array(
			'Через сайт' => __('feedback_pay_site'), //Через сайт
			'Через СМС' => __('feedback_pay_sms') //Через СМС
		);
		
		return new FeedbackForm(
						$messageTypes, $payTypes,
						isset($_GET['title']) ? $_GET['title'] : null, 
						isset($_GET['msg'])   ? $_GET['msg']   : null, 
						isset($_GET['info'])  ? $_GET['info']  : null
					);
	}

	public function _draw() {
		$canvas = parent::_draw();
		$canvas[':attributes']['baseclass'] = __CLASS__;
		return $canvas;
	}
}

class FeedbackForm extends Form {
	
	public function __construct(array $messageTypes, array $payTypes, $title = null, $message = null, $info = null) {
		parent::__construct();
		
		$this->title(__('FEEDBACK_FORM_TITLE')); //Форма обратной связи
		
		$this->name = new iString;
		$this->name->title(__('FEEDBACK_FORM_NAME')); //Ваше имя
		$this->name->alert('.+', __('FEEDBACK_FORM_MESSAGE_ALERT'), ALERT_NOT_MATCH);
		
		$this->email = new iEmail;
		$this->email->title(__('FEEDBACK_FORM_EMAIL')); //Ваш e-mail
		$this->name->alert('.+', __('FEEDBACK_FORM_MESSAGE_ALERT'), ALERT_NOT_MATCH);
		
		$this->phone = new iPhone;
		$this->phone->title(__('FEEDBACK_FORM_PHONE')); //Ваш телефон
		$this->name->alert('.+', __('FEEDBACK_FORM_MESSAGE_ALERT'), ALERT_NOT_MATCH);
		if (Engine::instance()->session->authorized()) {
			$client = new Client((string) Engine::instance()->session->clientId);
			if ($client->accounts)
			foreach ($client->accounts as $account) {
				if ($account->kind == 'PHONE') {
					$this->phone->set($account->deatails['number']);
					break;
				}
			}
		}

		$this->typemsg = new iSelect($messageTypes);
		$this->typemsg->title(__('FEEDBACK_FORM_REQUEST_TYPE_TITLE'));	//Тип запроса
		$this->typemsg->alert('^.+$', __('FEEDBACK_FORM_REQUEST_TYPE_ALERT'),ALERT_MATCH); //Выберите значение
		
		$this->typepay = new iSelect($payTypes);
		$this->typepay->title(__('FEEDBACK_FORM_PAYMENT_TYPE_TITLE')); //Тип платежа
		$this->typepay->optional(true);
		
		$this->message = new iText;				
		$this->message->title(__('FEEDBACK_FORM_MESSAGE')); //Сообщение
		$this->message->alert('.+', __('FEEDBACK_FORM_MESSAGE_ALERT'), ALERT_NOT_MATCH); //Это поле обязательно для заполнения
		
		$this->client = new iHidden();
		$this->client->optional(true);
		if (Engine::instance()->session->authorized())
			$this->client->set(Engine::instance()->session->clientId);
					
		//поле для названия страницы с которой был переход на feedback
		$this->msg_title = new iHidden;	
		$this->msg_title->optional(true);
		
		if ($title) 			
			$this->msg_title->set($title);		
		
		//предзаполняем текст сообщения
		if ($message)
			$this->message->set($message);		
		
		$this->info = new iHidden;
		$this->info->optional(true);
		
		if ($info)			
			$this->info->set($info);
		
		$this->submit = new iSubmit;
		$this->submit->title(__('FEEDBACK_FORM_SUBMIT'));
	}	
}
?>
