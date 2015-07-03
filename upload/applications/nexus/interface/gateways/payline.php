<?php
/**
 * @brief		Payline Gateway
 * @author		<a href='http://skinod.com.com'>Skinod</a>
 * @copyright	(c) 2015 Skinod.com
 */

require_once '../../../../init.php';
\IPS\Session\Front::i();

try
{
	$transaction = \IPS\nexus\Transaction::load( \IPS\Request::i()->nexusTransactionId );
	
	if ( $transaction->status !== \IPS\nexus\Transaction::STATUS_PENDING )
	{
		throw new \OutofRangeException;
	}
}
catch ( \OutOfRangeException $e )
{
	\IPS\Output::i()->redirect( \IPS\Http\Url::internal( "app=nexus&module=payments&controller=checkout&do=transaction&id=&t=" . \IPS\Request::i()->nexusTransactionId, 'front', 'nexus_checkout', \IPS\Settings::i()->nexus_https ) );
}

try
{
	$result = $transaction->method->api(
		array(
			'trans_id' 				=> \IPS\Request::i()->trans_id,
			'id_get' 				=> \IPS\Request::i()->id_get,
		), TRUE
	);

	if($result === 1) {
		$transaction->gw_id = \IPS\Request::i()->trans_id . '_' . \IPS\Request::i()->id_get;
		$transaction->save();
		$transaction->checkFraudRulesAndCapture( NULL );
		$transaction->sendNotification();
		\IPS\Session::i()->setMember( $transaction->invoice->member ); // This is in case the checkout was a guest, meaning checkFraudRulesAndCapture() may have just created an account. There is no security issue as we have just verified they were just bounced back from Payline
		\IPS\Output::i()->redirect( $transaction->url() );
	}
	
	throw new \OutofRangeException;	
}
catch ( \Exception $e )
{
	\IPS\Output::i()->redirect( $transaction->invoice->checkoutUrl()->setQueryString( array( '_step' => 'checkout_pay', 'err' => $transaction->member->language()->get( 'gateway_err' ) ) ) );
}