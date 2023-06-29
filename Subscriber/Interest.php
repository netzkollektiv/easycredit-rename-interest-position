<?php
namespace EasyCreditRenameInterest\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;

class Interest implements SubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SendMail_BeforeSend' => 'onOrderSave',
	];
    }

    public function onOrderSave(\Enlight_Event_EventArgs $args) {
        $orderVariables = $args->get('variables');
        if (!isset($orderVariables['additional']['payment']['name'])
            || $orderVariables['additional']['payment']['name'] != 'easycredit'
        ) {
            return;
        }

        $context = $args->get('context');
        $orderNumber = $context['sOrderNumber'];
        if (empty($orderNumber)) {
            return;
        }

       $newArticleOrderNumber = Shopware()->Config()->get('easycreditRenameInterestOrderNumber');
       if (!$newArticleOrderNumber) {
            return;
        }

        try {
            Shopware()->Db()->beginTransaction();

            // subtract interest from total amount
	    Shopware()->Db()->query("UPDATE s_order_details od
                    INNER JOIN s_order o ON od.orderID = o.id AND articleordernumber = ?
                SET
		    od.articleordernumber = ?
                WHERE o.ordernumber = ?", ['sw-payment-ec-interest', $newArticleOrderNumber, $orderNumber]
            );

            Shopware()->Db()->commit();
        } catch (\Exception $e) {
            Shopware()->Db()->rollBack();
            throw new \Enlight_Exception("Rename of interest failed:" . $e->getMessage(), 0, $e);
        }

    }
}
