<?php

namespace App\Http\Controllers;

use BackendProgramer\SnappPay\enums\Currency;
use BackendProgramer\SnappPay\Order\Order;
use BackendProgramer\SnappPay\Order\OrderProduct;
use BackendProgramer\SnappPay\Order\ProductCategory;
use BackendProgramer\SnappPay\SnappPay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class PaymentGatewaySnappPayController extends Controller
{
    /**
     * Order used in snappPay
     *
     * @var Order
     */
    private Order $order;

    /**
     * Calculate order and check is merchant eligible in snappPay
     *
     * @return \ErrorException|RedirectResponse|Redirector
     */
    public function calculateOrderAndCheckIsMerchantEligible(): \ErrorException|Redirector|RedirectResponse
    {
        // You can make manual settings as follows
        // $snappPaySetting = SnappPaySetting::credentials(
        //        'bamilo-user1',
        //        '123456789',
        //        'bamilo1',
        //        '987654321',
        //        'https://fms-gateway-staging.apps.public.teh-1.snappcloud.io/'
        //    );
        //
        // $snappPay = new SnappPay($snappPaySetting);

        // get setting from env or config file snapp-pay
        $snappPay = new SnappPay();


        $order = new Order(123, 153000, 170000, 10000, 0, Currency::TOMAN, '09121231111');
        $category = new ProductCategory('Electronics', 2);
        $orderProduct1 = new OrderProduct(1, 'Product 1', 10000, 9000, 2, $category);
        $orderProduct2 = new OrderProduct(2, 'Product 2', 50000, 45000, 3, $category);

        // You can add the amount of each product to the total price and price of the order as follows
        // $order->addProduct($orderProduct1, true);
        // $order->addProduct($orderProduct2, true);

        $order->addProduct($orderProduct1);
        $order->addProduct($orderProduct2);

        // Check isMerchantEligible
        $merchantEligible = $snappPay->isMerchantEligible($order->getPrice(), Currency::TOMAN);

        if ($merchantEligible['successful'] &&
            isset($merchantEligible['response']['eligible']) &&
            $merchantEligible['response']['eligible']) {

            // Get paymentToken
            $paymentToken = $snappPay->getPaymentToken($order, 'https://example.com/payment/succsses/'.$order->getId(), now());

            if ($paymentToken['successful'] &&
                isset($paymentToken['response']['paymentToken'])) {

                // Save paymentToken in order
                $order->setPaymentToken($paymentToken['response']['paymentToken']);

                $this->order = $order;
                return redirect($paymentToken['response']['paymentPageUrl']);
            }
        }
        return new \ErrorException('Your order cannot be paid with Snap Pay');
    }

    /**
     * Check payment and execute verify order and settle it in snappPay
     *
     * @param $orderId
     * @return string[]|\ErrorException
     */
    public function paymentSuccess($orderId): array|\ErrorException
    {
        // Find order in your DB
        if ($orderId == $this->order->getId()) {
            // get setting from env or config file snapp-pay
            $snappPay = new SnappPay();

            try {
                // Calculations before the end of the purchase,
                // such as reducing the product inventory and final registration of the order


                // Verify order
                $resultVerify = $snappPay->verifyOrder($this->order->getPaymentToken());
            } catch (\Exception $exception) {
                // If there is a problem in the order registration process after verifying the order, revert it
                $snappPay->revertOrder($this->order->getPaymentToken());

                return new \ErrorException('An error occurred in the order registration process');
            }

            // Check verify order is successful
            if($resultVerify['successful'] && isset($resultVerify['response']['transactionId'])) {
                // Finally, if everything is successful, settle the order
                $resultSettle = $snappPay->settleOrder($this->order->getPaymentToken());
                // Check settle order is successful
                if($resultSettle['successful'] && isset($resultSettle['response']['transactionId'])) {
                    return ['status' => 'success', 'message' => 'The order was successfully placed.'];
                }
                return new \ErrorException('Your order could not be settled');
            }
            return new \ErrorException('Your order could not be verify');
        }
        return new \ErrorException('Your order was not found');
    }

    /**
     * Update order in snappPay
     *
     * @param $orderId
     * @return \ErrorException|string[]
     */
    public function updateOrder($orderId): array|\ErrorException
    {
        // Find order in your DB
        if ($orderId == $this->order->getId()) {
            // get setting from env or config file snapp-pay
            $snappPay = new SnappPay();

            // Find product in your order
            if($this->order->getOrderProduct(2)) {
                // Remove product from order
                $this->order->removeProduct(2,true);
                // Update order
                $resultUpdate = $snappPay->updateOrder($this->order);
                // Check update order is successful
                if($resultUpdate['successful'] && isset($resultUpdate['response']['transactionId'])) {
                    return ['status' => 'success', 'message' => 'Update order was successfully.'];
                }
                return new \ErrorException('Your order could not be updated');
            }
            return new \ErrorException('product was not found in the order');
        }
        return new \ErrorException('Your order was not found');
    }

    /**
     * Cancel order in snappPay
     *
     * @param $orderId
     * @return \ErrorException|string[]
     */
    public function cancelOrder($orderId): array|\ErrorException
    {
        // Find order in your DB
        if ($orderId == $this->order->getId()) {
            // get setting from env or config file snapp-pay
            $snappPay = new SnappPay();
            // Cancel order
            $resultCancel = $snappPay->cancelOrder($this->order->getPaymentToken());
            // Check cancel order is successful
            if($resultCancel['successful'] && isset($resultCancel['response']['transactionId'])) {
                return ['status' => 'success', 'message' => 'Update order was successfully.'];
            }
            return new \ErrorException('Your order could not be updated');
        }
        return new \ErrorException('Your order was not found');
    }
}
