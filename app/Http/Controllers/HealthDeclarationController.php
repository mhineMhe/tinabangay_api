<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\HealthDeclaration;
use Carbon\Carbon;
class HealthDeclarationController extends APIController
{

  public $notificationClass = 'Increment\Common\Notification\Http\NotificationController';
  public $merchantClass = 'Increment\Imarket\Merchant\Http\MerchantController';

  function __construct(){
    $this->model = new HealthDeclaration();
    $this->notRequired = array(
      'content'
    );
  }

  public function create(Request $request){
    $data = $request->all();

    // FOR HEALTH DECLARATION FORMAT
    $health_dec_format = null;
    $health_dec_content = json_decode($data['content']);
    if (isset($health_dec_content) && isset($health_dec_content->format)) {
      $health_dec_format = $health_dec_content->format;
    }

    $data['code'] = $this->generateCode();
    $this->model = new HealthDeclaration();
    $this->insertDB($data);
    if($this->response['data'] > 0){
      // send notification
      $notification = array(
        'from'          => $data['from'],
        'to'            => $data['to'],
        'payload'       => 'form_request/'.$health_dec_format,
        'payload_value' => $this->response['data'],
        'route'         => '/form/'.$data['code'],
        'created_at'    => Carbon::now()
      );
      app($this->notificationClass)->createByParams($notification);
    }
    return $this->response();
  }

  public function retrieve(Request $request){
    $data = $request->all();
    $this->retrieveDB($data);
    if(sizeof($this->response['data']) > 0){
      $i = 0;
      $result = $this->response['data'];
      foreach ($result as $key) {
        $this->response['data'][$i]['merchant'] = app($this->merchantClass)->getByParams('account_id', $result[$i]['owner']);
        $i++;
      }
    }
    return $this->response();
  }

  public function update(Request $request){
    $data = $request->all();

    // FOR HEALTH DECLARATION FORMAT
    $health_dec_format = null;
    $health_dec_content = json_decode($data['content']);
    if (isset($health_dec_content) && isset($health_dec_content->format)) {
      $health_dec_format = $health_dec_content->format;
    }

    $this->updateDB($data);
    if($this->response['data'] == true){
      $notification = array(
        'from'          => $data['from'],
        'to'            => $data['to'],
        'payload'       => 'form_submitted/'.$health_dec_format,
        'payload_value' => $data['id'],
        'route'         => '/form/'.$data['code'],
        'created_at'    => Carbon::now()
      );
      app($this->notificationClass)->createByParams($notification);
    }
    return $this->response();
  }

  public function generateCode(){
    $code = 'HDF-'.substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"), 0, 60);
    $codeExist = HealthDeclaration::where('code', '=', $code)->get();
    if(sizeof($codeExist) > 0){
      $this->generateCode();
    }else{
      return $code;
    }
  }
}
