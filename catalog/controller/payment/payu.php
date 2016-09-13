<?php
/*
 * @support
 * http://www.opensourcetechnologies.com/contactus.html
 * sales@opensourcetechnologies.com
* */
class ControllerPaymentPayu extends Controller {
	public function index() {
    	$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');
		$this->language->load('payment/payu');
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		$data['merchant'] = $this->config->get('payu_merchant');
		
		if($this->config->get('payu_test')=='demo')
			$data['action'] = 'https://test.payu.in/_payment.php';
		else
		    $data['action'] = 'https://secure.payu.in/_payment.php';
			
		$txnid        = 	$this->session->data['order_id'];

		
                
		$data['key'] = $this->config->get('payu_merchant');
		$data['salt'] = $this->config->get('payu_salt');
		$data['txnid'] = $txnid;
		$data['amount'] = (int)$order_info['total'];
		$data['productinfo'] = 'opencart products information';
		$data['firstname'] = $order_info['payment_firstname'];
		$data['Lastname'] = $order_info['payment_lastname'];
		$data['Zipcode'] = $order_info['payment_postcode'];
		$data['email'] = $order_info['email'];
		$data['phone'] = $order_info['telephone'];
		$data['address1'] = $order_info['payment_address_1'];
        $data['address2'] = $order_info['payment_address_2'];
        $data['state'] = $order_info['payment_zone'];
        $data['city']=$order_info['payment_city'];
        $data['country']=$order_info['payment_country'];
		$data['Pg'] = 'CC';
		$data['surl'] = $this->url->link('payment/payu/callback');
		$data['Furl'] = $this->url->link('payment/payu/callback');
		$data['curl'] = $this->url->link('checkout/checkout', '', 'SSL');
		$key          =  $this->config->get('payu_merchant');
		$amount       = (int)$order_info['total'];
		$productInfo  = $data['productinfo'];
		$firstname    = $order_info['payment_firstname'];
		$email        = $order_info['email'];
		$salt         = $this->config->get('payu_salt');
		
		 $Hash=hash('sha512', $key.'|'.$txnid.'|'.$amount.'|'.$productInfo.'|'.$firstname.'|'.$email.'|||||||||||'.$salt); 
		
		$data['user_credentials'] = $data['key'].':'.$data['email'];
		$data['Hash'] = $Hash;
		
			
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/payu.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/payment/payu.tpl', $data);
		} else {
			return $this->load->view('default/template/payment/payu.tpl', $data);
		}	
		
		
		
		
	}
	
	public function callback() {
		if (isset($this->request->post['key']) && ($this->request->post['key'] == $this->config->get('payu_merchant'))) {
			$this->language->load('payment/payu');
			
			$data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));

			if (!isset($this->request->server['HTTPS']) || ($this->request->server['HTTPS'] != 'on')) {
				$data['base'] = HTTP_SERVER;
			} else {
				$data['base'] = HTTPS_SERVER;
			}
		
			$data['charset'] = $this->language->get('charset');
			$data['language'] = $this->language->get('code');
			$data['direction'] = $this->language->get('direction');
			$data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));
			$data['text_response'] = $this->language->get('text_response');
			$data['text_success'] = $this->language->get('text_success');
			$data['text_success_wait'] = sprintf($this->language->get('text_success_wait'), $this->url->link('checkout/success'));
			$data['text_failure'] = $this->language->get('text_failure');
			$data['text_cancelled'] = $this->language->get('text_cancelled');
			$data['text_cancelled_wait'] = sprintf($this->language->get('text_cancelled_wait'), $this->url->link('checkout/cart'));
			$data['text_pending'] = $this->language->get('text_pending');
			$data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->url->link('checkout/cart'));
			
			 $this->load->model('checkout/order');
			 $orderid = $this->request->post['txnid'];
			 $order_info = $this->model_checkout_order->getOrder($orderid);
			 
			 
				$key          		=  	$this->request->post['key'];
				$amount      		= 	$this->request->post['amount'];
				$productInfo  		= 	$this->request->post['productinfo'];
				$firstname    		= 	$this->request->post['firstname'];
				$email        		=	$this->request->post['email'];
				$salt        		= 	$this->config->get('payu_salt');
				$txnid		 		=   $this->request->post['txnid'];
				$keyString 	  		=  	$key.'|'.$txnid.'|'.$amount.'|'.$productInfo.'|'.$firstname.'|'.$email.'||||||||||';
				$keyArray 	  		= 	explode("|",$keyString);
				$reverseKeyArray 	= 	array_reverse($keyArray);
				$reverseKeyString	=	implode("|",$reverseKeyArray);
			 
			 
			 if (isset($this->request->post['status']) && $this->request->post['status'] == 'success') {
			 	$saltString     = $salt.'|'.$this->request->post['status'].'|'.$reverseKeyString;
				$sentHashString = strtolower(hash('sha512', $saltString));
			 	$responseHashString=$this->request->post['hash'];
				
				$order_id = $this->request->post['txnid'];
				$message = '';
				$message .= 'orderId: ' . $this->request->post['txnid'] . "\n";
				$message .= 'Transaction Id: ' . $this->request->post['mihpayid'] . "\n";
				foreach($this->request->post as $k => $val){
					$message .= $k.': ' . $val . "\n";
				}
					if($sentHashString==$this->request->post['hash']){
					
							$this->model_checkout_order->addOrderHistory($this->request->post['txnid'], $this->config->get('payu_order_status_id'), $message,false);
							$data['continue'] = $this->url->link('checkout/success');
							$data['column_left'] = $this->load->controller('common/column_left');
							$data['column_right'] = $this->load->controller('common/column_right');
							$data['content_top'] = $this->load->controller('common/content_top');
							$data['content_bottom'] = $this->load->controller('common/content_bottom');
							$data['footer'] = $this->load->controller('common/footer');
							$data['header'] = $this->load->controller('common/header');

							if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/payu_success.tpl')) {
								$this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/payu_success.tpl', $data));
							} else {
								$this->response->setOutput($this->load->view('default/template/payment/payu_success.tpl', $data));
							}
					  } else {
							$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], 1, $message, false);
							$data['continue'] = $this->url->link('checkout/checkout', '', 'SSL');
							$data['column_left'] = $this->load->controller('common/column_left');
							$data['column_right'] = $this->load->controller('common/column_right');
							$data['content_top'] = $this->load->controller('common/content_top');
							$data['content_bottom'] = $this->load->controller('common/content_bottom');
							$data['footer'] = $this->load->controller('common/footer');
							$data['header'] = $this->load->controller('common/header');

							if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/payu_pending.tpl')) {
								$this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/payu_pending.tpl', $data));
							} else {
								$this->response->setOutput($this->load->view('default/template/payment/payu_pending.tpl', $data));
							}		
					  } 	
			 
			 
			 }else {
    			$data['continue'] = $this->url->link('checkout/cart');
				$data['column_left'] = $this->load->controller('common/column_left');
				$data['column_right'] = $this->load->controller('common/column_right');
				$data['content_top'] = $this->load->controller('common/content_top');
				$data['content_bottom'] = $this->load->controller('common/content_bottom');
				$data['footer'] = $this->load->controller('common/footer');
				$data['header'] = $this->load->controller('common/header');

				if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/payu_failure.tpl')) {
					$this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/payu_failure.tpl', $data));
				} else {
					$this->response->setOutput($this->load->view('default/template/payment/payu_failure.tpl', $data));
				}						
			}
		}
	}
}
?>
