<?php

/**
 * SafetypayBundle for Symfony2
 *
 * This Bundle is part of Symfony2 Payment Suite
 *
 * @package SafetypayBundle
 *
 */

namespace Scastells\SafetypayBundle\Services\Wrapper;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use PaymentSuite\PaymentCoreBundle\Services\interfaces\PaymentBridgeInterface;
use Scastells\SafetypayBundle\Services\SafetypayManager;
use Symfony\Component\Form\FormFactory;
use PaymentSuite\PaymentCoreBundle\Exception\PaymentException;
use Scastells\SafetypayBundle\SafetypayMethod;

/**
 * SafetypayBundle manager
 */
class SafetypayTypeWrapper
{

    /**
     * @var FormFactory
     * 
     * Form factory
     */
    protected $formFactory;


    /**
     * @var PaymentBridge
     * 
     * Payment bridge
     */
    private $paymentBridge;


    /**
     * @var string
     *
     * Encryption key
     */
    private $key;


    /**
     * @var string
     * 
     * User id
     */
    private $signature;

    /**
     * @var Connect
     *
     * connect Manager for SafetyPay
     */
    private $safetyPayManager;

    /**
     * @var string
     *
     * configuration value
     */
    private $expiration;


    /**
     * Formtype construct method
     *
     * @param FormFactory $formFactory Form factory
     * @param PaymentBridgeInterface $paymentBridge Payment bridge
     * @param $key
     * @param $signature
     * @param safetypayManager $safetyPayManager
     * @param $expiration
     */
    public function __construct(FormFactory $formFactory, PaymentBridgeInterface $paymentBridge, $key, $signature, safetypayManager $safetyPayManager, $expiration)
    {
        $this->formFactory = $formFactory;
        $this->paymentBridge = $paymentBridge;
        $this->key = $key;
        $this->signature = $signature;
        $this->safetyPayManager = $safetyPayManager;
        $this->expiration = $expiration;
    }


    /**
     * Builds form given success and fail urls
     *
     * @param String $successRoute
     * @param String $failRoute
     * @param Integer $safetyPayTransaction
     * @param SafetypayMethod $paymentMethod
     * @throws \PaymentSuite\PaymentCoreBundle\Exception\PaymentException
     * @return FormBuilder
     */
    public function buildForm($successRoute, $failRoute, $safetyPayTransaction, SafetypayMethod $paymentMethod)
    {
        $formBuilder = $this
            ->formFactory
            ->createNamedBuilder(null);

        $elements = array(
            'Apikey'                => $this->key,
            'RequestDateTime'       => $this->safetyPayManager->getRequestDateTime(),
            'CurrencyCode'          => $this->paymentBridge->getCurrency(),
            'Amount'                => number_format($this->paymentBridge->getAmount(), 2, '.', ''),
            'MerchantReferenceNo'   => $safetyPayTransaction,
            'Language'              => 'ES',
            'TrackingCode'          => '',
            'ExpirationTime'        => $this->expiration,
            'FilterBy'              => '',
            'TransactionOkURL'      => $successRoute,
            'TransactionErrorURL'   => $failRoute,
            'ResponseFormat'        => $this->safetyPayManager->getresponseFormat()
        );

        $elements['signature'] = $this->safetyPayManager->getSignature(
            $elements,
            'CurrencyCode, Amount, MerchantReferenceNo, Language,
            TrackingCode, ExpirationTime, TransactionOkURL,
            TransactionErrorURL'
        );

        $paymentMethod->setRequestDateTime($elements['RequestDateTime']);
        $paymentMethod->setSignature($elements['signature']);

        $urlToken = $this->safetyPayManager->getUrlToken($elements, false);
        //Token no valid
        if (strpos($urlToken, 'Error') > 0) {
            throw new PaymentException;
        }
            $urlTokenExploded = explode('?', $urlToken);
            $urlTokenHost = $urlTokenExploded[0];
            $urlTokenParam = $urlTokenExploded[1];
            $urlTokenParamExploded = explode('=', $urlTokenParam);

        $formBuilder
            ->setAction($urlTokenHost)
            ->setMethod('POST')

            /**
             * Parameters injected by construct
             */
            ->add('TokenID', 'hidden', array(
                'data' => $urlTokenParamExploded[1],
            ));

        return $formBuilder;
    }

}