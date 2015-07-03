<?php
/**
 * @brief		Payline Gateway
 * @author		<a href='http://skinod.com.com'>Skinod</a>
 * @copyright	(c) 2015 Skinod.com
 */

namespace IPS\nexus\Gateway;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * PayPal Gateway
 */
class _Payline extends \IPS\nexus\Gateway
{
	// URL also Can be https://ir.payline.com/pg/services/WebGate/wsdl
	const PAYLINE_SEND_URL = 'http://payline.ir/payment/gateway-send';
	const PAYLINE_CHECK_URL = 'http://payline.ir/payment/gateway-result-second';

	/**
	 * Check the gateway can process this...
	 *
	 * @param	$amount			\IPS\nexus\Money	The amount
	 * @param	$billingAddress	\IPS\GeoLocation	The billing address
	 * @return	bool
	 */
	public function checkValidity( \IPS\nexus\Money $amount, \IPS\GeoLocation $billingAddress )
	{
		// only accept IRR
		if ($amount->currency != 'IRR')
		{
			return FALSE;
		}
				
		return parent::checkValidity( $amount, $billingAddress );
	}
		
	/* !Payment Gateway */
		
	/**
	 * Authorize
	 *
	 * @param	\IPS\nexus\Transaction					$transaction	Transaction
	 * @param	array|\IPS\nexus\Customer\CreditCard	$values			Values from form OR a stored card object if this gateway supports them
	 * @param	\IPS\nexus\Fraud\MaxMind\Request|NULL	$maxMind		*If* MaxMind is enabled, the request object will be passed here so gateway can additional data before request is made	
	 * @return	\IPS\DateTime|NULL		Auth is valid until or NULL to indicate auth is good forever
	 * @throws	\LogicException			Message will be displayed to user
	 */
	public function auth( \IPS\nexus\Transaction $transaction, $values, \IPS\nexus\Fraud\MaxMind\Request $maxMind = NULL )
	{
		$transaction->save();

		$data = array(
			'amount'		=> $transaction->amount->amount,
			'redirect' 		=> urlencode((string) \IPS\Settings::i()->base_url . 'applications/nexus/interface/gateways/payline.php?nexusTransactionId=' . $transaction->id)
		);

		$result = $this->api($data);

		if($result > 0) {
			\IPS\Output::i()->redirect( \IPS\Http\Url::external( "http://payline.ir/payment/gateway-{$result}" ) );
		}

		throw new \RuntimeException;
	}
	
	/**
	 * Capture
	 *
	 * @param	\IPS\nexus\Transaction	$transaction	Transaction
	 * @return	void
	 * @throws	\LogicException
	 */
	public function capture( \IPS\nexus\Transaction $transaction ) {

	}
				
	// 	throw new \RuntimeException;
	// }
		
	/* !ACP Configuration */
	
	/**
	 * Settings
	 *
	 * @param	\IPS\Helpers\Form	$form	The form
	 * @return	void
	 */
	public function settings( &$form )
	{
		$settings = json_decode( $this->settings, TRUE );
		$form->add( new \IPS\Helpers\Form\Text( 'payline_api', $this->id ?$settings['api']:'', TRUE ) );
	}
	
	/**
	 * Test Settings
	 *
	 * @param	array	$settings	Settings
	 * @return	array
	 * @throws	\InvalidArgumentException
	 */
	public function testSettings( $settings )
	{		
		return $settings;
	}

	/* !Utility Methods */
	
	/**
	 * Send API Request
	 *
	 * @param	array	$data	The data to send
	 * @return	array
	 */
	public function api( $data, $verify = FALSE )
	{
		$data['api'] = json_decode( $this->settings )->api;
		return intval((string) \IPS\Http\Url::external( $verify?self::PAYLINE_CHECK_URL:self::PAYLINE_SEND_URL)->request()->post( $data ));
	}

}