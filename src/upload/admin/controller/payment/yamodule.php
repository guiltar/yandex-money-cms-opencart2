<?php
class ControllerPaymentYamodule extends Controller {

	public function index() {
		$this->load->language('payment/yamodule');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->response->redirect($this->url->link('feed/yamodule', 'token=' . $this->session->data['token'], 'SSL'));
	}

	public function invoicemail (){
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('yamodule/email-inv.tpl', $data));
	}

	public function test(){
		$data = array();
		$this->load->model('setting/setting');
		$sid = $this->config->get('ya_kassa_sid');
		$sсid = $this->config->get('ya_kassa_scid');
		$psw = $this->config->get('ya_kassa_pw');
		$url = str_replace("http://","https://",HTTPS_CATALOG).'index.php?route=payment/yamodule/callback';

		$data['zeroTest'] = new checkSetting(array('shopId'=>$sid, 'scid'=> $sсid, 'shopPassword' => $psw));
		$firstTest = new checkConnection(array('url'=>$url));
		$data['firstTest'] = &$firstTest;
		$data['listTests'] = array('zeroTest','firstTest');
		if (!empty($firstTest->resultData)) $data['listTests'][]='secondTest';
		$data['secondTest'] = new checkXmlAnswer(array('raw'=> $firstTest->resultData, 'url' => $url, 'shopId'=>$sid));
		//
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('yamodule/check.tpl', $data));
	}
	public function install() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('yamodule_status', array('yamodule_status' => 1));
	}
	
	public function uninstall() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('yamodule_status', array('yamodule_status' => 0));
	}
}
/*
 * Класс для отправки запросов с предопределенными данными (методом setType)
 */
class TestRequest {
	public static $url;
	public static $respHead;
	public static $respBody;
	public static $respInfo;
	public static $respError;

	private static $data;
	private static $varPost = array(
		"invoiceId"=>"123",
		"orderNumber"=>"1",
		"orderSumAmount"=>"10.00",
		"shopArticleId"=>"1",
		"paymentType"=>"PC",
		"action"=>"checkOrder",
		"shopId"=>"12345",
		"scid"=>"54321",
		"shopSumBankPaycash"=>"",
		"shopSumCurrencyPaycash"=>"",
		"orderSumBankPaycash"=>"",
		"orderSumCurrencyPaycash"=>"",
		"customerNumber"=>"",
		"md5"=>"",
	);

	public static function setType($type){
		$data = self::$varPost;
		switch($type){
			case "check":
				$data['action'] = 'checkOrder';
				break;
			case "aviso":
				$data['action'] = 'paymentAviso';
				break;
			default:
				break;
		}
		self::$data = $data;
	}
	public static function request(){
		$curlOpt = array(
			CURLOPT_HEADER => 1,
			CURLOPT_NOBODY => 0,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST =>  false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_MAXREDIRS => 1,
			CURLINFO_HEADER_OUT => true,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query(self::$data),
			CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded'),
		);
		$curl = curl_init(self::$url);
		curl_setopt_array($curl, $curlOpt);

		$raw = curl_exec($curl);

		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		self::$respInfo = curl_getinfo($curl);
		self::$respHead = substr($raw, 0, $header_size);
		self::$respBody = substr($raw, $header_size);
		self::$respError = curl_error($curl);
		curl_close($curl);
		if ($raw === false) {
			return false;
		}
		return true;
	}
}

/*
 *
 */
class TestParse{
	public static $raw = '';
	public static $optFind = array();
	public static $arResult = array();

	public static function parseXML($level=false){
		$answer=array();
		$doc = new DOMDocument();
		@$doc->loadXML(self::$raw);
		if (empty($doc->firstChild)) return false;
		$order_xml=($level)?$doc->firstChild->firstChild:$doc->firstChild;
		foreach (self::$optFind as $name) if (method_exists($order_xml,'hasAttribute') && $order_xml->hasAttribute($name)) $answer[$name]=$order_xml->getAttributeNode($name)->value;
		self::$arResult = $answer;
		return count($answer)>0;
	}
}
/*
 *
 */
class TestBaseClass{
	public $resultData='';
	public $name = "Original";
	public $done = false;
	public $readyNext = true;
	protected $conf = array();
	protected $warning = array();
	protected $successText = "<span class=\"label label-success\">ОК</span>";
	protected $failText = "";

	protected function runTest(){
		return true;
	}
	public function getWarn(){
		if (count($this->warning)>0) return $this->warning;
		return array();
	}

	public function getTitle(){
		return $this->name;
	}

	public function getWarnHtml(){
		$html = "<div class=''>";
		foreach ($this->warning as $item){
			$html.= sprintf("<div class=\"alert alert-warning\"><b>%s</b><p>%s</p></div>", $item['text'], $item['action']);
		}
		return $html."</div>";
	}

	public function getResult(){
		//$this->runTest();
		if ($this->done == true){
			return $this->successText;
		}
		return $this->failText;
	}
}

class checkSetting extends TestBaseClass{
	public $name;

	const NAME = "Проверка параметров магазина";

	const ERROR_SHOPID = "Ошибка в идентификаторе магазина";
	const ERROR_SHOPID_ACTION = "Параметр в поле Shop ID указан неправильно или не указан вообще.
    Чтобы не ошибиться, скопируйте ваш Shop ID в <a href='https://wiki.yamoney.ru/money.yandex.ru/my'>личном кабинете Яндекс.Кассы</a> и вставьте его в настройки модуля.
    Затем повторите проверку.";

	const ERROR_SCID = "Ошибка в номере витрины магазина";
	const ERROR_SCID_ACTION = "Параметр в поле scid указан неправильно или не указан вообще.
	Чтобы не ошибиться, скопируйте ваш scid в <a href='https://wiki.yamoney.ru/money.yandex.ru/my'>личном кабинете Яндекс.Кассы</a> и вставьте его в настройки модуля.
	Затем повторите проверку.";

	const ERROR_SHOPPSW = "Ошибка в секретном слове магазина";
	const ERROR_SHOPPSW_ACTION = "Параметр в поле ShopPassword указан неправильно или не указан вообще.
	Чтобы не ошибиться, скопируйте ваш ShopPassword в <a href='https://wiki.yamoney.ru/money.yandex.ru/my'>личном кабинете Яндекс.Кассы</a> и вставьте его в настройки модуля.
	Затем повторите проверку.";

	public function __construct($conf){
		$this->conf = $conf;
		$this->name = self::NAME;
		$this->runTest();
	}
	public function getTitle(){
		return $this->name;
	}
	protected function runTest(){
		if (intval($this->conf['shopId']) != $this->conf['shopId'] || $this->conf['shopId']<=0){
			$this->warning[]=array('text'=> self::ERROR_SHOPID , 'action'=> self::ERROR_SHOPID_ACTION);
		}
		if ($this->conf['scid']<=0){
			$this->warning[]=array('text'=> self::ERROR_SCID, 'action'=> self::ERROR_SCID_ACTION);
		}
		if (strlen($this->conf['shopPassword'])>20){
			$this->warning[]=array('text'=> self::ERROR_SHOPPSW, 'action'=>self::ERROR_SHOPPSW_ACTION);
		}

		if (count($this->warning)==0) $this->done = true;
	}
}

/**
 * Created by PhpStorm.
 * User: ivkarmanov
 * Date: 20.05.2016
 * Time: 12:27
 */

class checkConnection extends TestBaseClass{
	public $name;

	const NAME = "Соединение по checkURL/avisoURL";

	const ERROR_30x = "Ошибка: запрос сервера был перенаправлен";
	const ERROR_30x_ACTION = "Убедитесь, что ваш сервер работает по протоколу HTTPS, и проверьте правила обработки запросов (mod_rewrite, htaccess, rewrite).
        Отключите заглушки, которые могут перенаправлять запросы сервера на другие страницы вашего сайта. Затем повторите проверку.
        Информация для вебмастеров вашего сайта:
        Ответ веб-сервера на POST-запрос был выполнен с кодом %s и перенаправлением на адрес %s.";

	const ERROR_x = "Ошибка: ваш сайт не отвечает или отвечает неправильно";
	const ERROR_x_ACTION = "Убедитесь, что ваш сервер работает по протоколу HTTPS, и проверьте правила обработки запросов (mod_rewrite, htaccess, rewrite).
        Отключите заглушки, которые могут влиять на соединение с сайтом. Затем повторите проверку.
        Информация для вебмастеров вашего сайта:
        Код ответа сервера (%d) на POST-запрос не равен правильному коду (200).";

	const ERROR_0 = "Не получилось установить соединение с вашим сайтом";
	const ERROR_0_ACTION = "Чтобы закончить проверку, включите «Тестовый режим» в настройках модуля и сделайте тестовый платеж.
        Если он пройдет успешно, модуль работает правильно, и вы можете принимать реальные платежи.
        Если в процессе тестового платежа возникнет ошибка, напишите о ней специалистам Яндекс.Кассы на yamoney_shop@yamoney.ru.";


	public function __construct($conf){
		$this->conf = $conf;
		$this->name = self::NAME;
		$this->runTest();
	}
	public function getTitle(){
		return $this->name;
	}
	protected function runTest(){
		TestRequest::$url = $this->conf['url'];
		TestRequest::setType('check');
		if (TestRequest::request() === true){
			if (TestRequest::$respInfo['http_code']==200){
				$this->done = true;
				$this->resultData = TestRequest::$respBody;
			}elseif(!empty(TestRequest::$respInfo['redirect_url'])){
				$this->warning[]=array('text'=> self::ERROR_30x, 'action' => sprintf( self::ERROR_30x_ACTION, TestRequest::$respInfo['http_code'], TestRequest::$respInfo['redirect_url']));
			}else{
				$this->warning[]=array('text'=>self::ERROR_x, 'action' => sprintf(self::ERROR_x_ACTION, TestRequest::$respInfo['http_code']));
			}
		}else{
			$this->warning[]=array('text'=>self::ERROR_0, 'action' => self::ERROR_0_ACTION);
		}
	}
}

class checkXmlAnswer extends TestBaseClass{
	public $name;

	const NAME = "Соединение между сервером магазина и сервером Яндекс.Кассы";
	const ERROR_BAD_ATTR = "Ошибка в ответе вашего сервера";
	const ERROR_BAD_ATTR_ACTION = "Напишите об этом специалистам Яндекс.Кассы на yamoney_shop@yamoney.ru.
        В тему поставьте «Ошибки автотеста, Opencart 2, Shop Id %d».
        В письмо скопируйте следующий текст:
        «На тестовый POST-запрос по адресу %s был получен ответ, который не содержит обязательных параметров для работы с сервисом «Яндекс.Касса».
        Полный текст ответа: %s».";

	const ERROR_BAD_XML = "Сайт отвечает с ошибкой";
	const ERROR_BAD_XML_ACTION = "Убедитесь, что:
        — ваш сервер работает по протоколу HTTPS,
        — в ответе нет лишних символов,
        — в панели управления нет модулей, которые принудительно выводят информацию на все страницы сайта (например, панели чатов или рекламу хостинга).
        Затем повторите проверку.
        Информация для вебмастеров вашего сайта:
        При POST-запросе на адрес %s был получен документ, который отличается от xml-документа. Документ начинается со следующих символов (первые 20): %s.";

	public function __construct($conf){
		$this->name = self::NAME;
		$this->conf = $conf;
		$this->runTest();
	}

	protected function runTest(){
		TestParse::$raw = $this->conf['raw'];
		TestParse::$optFind = array('code', 'invoiceId', 'shopId');
		if (TestParse::parseXML()=== true){
			$arResult = TestParse::$arResult;
			if (isset($arResult['code']) && isset($arResult['invoiceId']) && isset($arResult['shopId'])){
				$this->done = true;
			}else{
				$this->warning[]= sprintf(self::ERROR_BAD_ATTR, $this->conf['shopId'], $this->conf['url'], $this->conf['raw']);
			}
		}else{
			$sLen = (strlen($this->conf['raw'])>=20)?20:strlen($this->conf['raw']);
			$this->warning[]=array(
				"text" => self::ERROR_BAD_XML,
				"action" => sprintf(self::ERROR_BAD_XML_ACTION, $this->conf['url'], substr($this->conf['raw'], 0, $sLen))
			);
		}
	}
}