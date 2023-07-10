<?php

/**
 * This file is part of the Spryker Commerce OS.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Zed\Oms;

use Pyz\Zed\Oms\Communication\Plugin\Oms\Command\PayCommandPlugin;
use Pyz\Zed\Oms\Communication\Plugin\Oms\Condition\IsAuthorizedConditionPlugin;
use Pyz\Zed\Oms\Communication\Plugin\Oms\InitiationTimeoutProcessorPlugin;
use Spryker\Zed\Availability\Communication\Plugin\Oms\AvailabilityReservationPostSaveTerminationAwareStrategyPlugin;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\SendOrderConfirmationPlugin;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\SendOrderShippedPlugin;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\SendOrderStatusChangedMessagePlugin;
use Spryker\Zed\Oms\Communication\Plugin\Oms\ReservationHandler\ReservationVersionPostSaveTerminationAwareStrategyPlugin;
use Spryker\Zed\Oms\Dependency\Plugin\Command\CommandCollectionInterface;
use Spryker\Zed\Oms\Dependency\Plugin\Condition\ConditionCollectionInterface;
use Spryker\Zed\Oms\OmsDependencyProvider as SprykerOmsDependencyProvider;
use Spryker\Zed\ProductBundle\Communication\Plugin\Oms\ProductBundleReservationPostSaveTerminationAwareStrategyPlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Oms\ProductPackagingUnitOmsReservationAggregationPlugin;
use Spryker\Zed\ProductPackagingUnit\Communication\Plugin\Reservation\LeadProductReservationPostSaveTerminationAwareStrategyPlugin;
use Spryker\Zed\SalesInvoice\Communication\Plugin\Oms\GenerateOrderInvoiceCommandPlugin;
use Spryker\Zed\SalesPayment\Communication\Plugin\Oms\SendCancelPaymentMessageCommandPlugin;
use Spryker\Zed\SalesPayment\Communication\Plugin\Oms\SendCapturePaymentMessageCommandPlugin;
use Spryker\Zed\SalesPayment\Communication\Plugin\Oms\SendRefundPaymentMessageCommandPlugin;
use Spryker\Zed\SalesReturn\Communication\Plugin\Oms\Command\StartReturnCommandPlugin;
use Spryker\Zed\Shipment\Dependency\Plugin\Oms\ShipmentManualEventGrouperPlugin;
use Spryker\Zed\Shipment\Dependency\Plugin\Oms\ShipmentOrderMailExpanderPlugin;
use Spryker\Zed\TaxApp\Communication\Plugin\Oms\Command\SubmitPaymentTaxInvoicePlugin;
use Spryker\Zed\TaxApp\Communication\Plugin\Oms\OrderRefundedEventListenerPlugin;

class OmsDependencyProvider extends SprykerOmsDependencyProvider
{
    /**
     * @var string
     */
    public const FACADE_TRANSLATOR = 'FACADE_TRANSLATOR';

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->extendCommandPlugins($container);
        $container = $this->extendCommandPlugins($container);
        $container = $this->extendConditionPlugins($container);

        return $container;
    }

    /**
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationPostSaveTerminationAwareStrategyPluginInterface>
     */
    protected function getReservationPostSaveTerminationAwareStrategyPlugins(): array
    {
        return [
            new ReservationVersionPostSaveTerminationAwareStrategyPlugin(),
            new AvailabilityReservationPostSaveTerminationAwareStrategyPlugin(),
            new ProductBundleReservationPostSaveTerminationAwareStrategyPlugin(),
            new LeadProductReservationPostSaveTerminationAwareStrategyPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsOrderMailExpanderPluginInterface>
     */
    protected function getOmsOrderMailExpanderPlugins(Container $container): array
    {
        return [
            new ShipmentOrderMailExpanderPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsManualEventGrouperPluginInterface>
     */
    protected function getOmsManualEventGrouperPlugins(Container $container): array
    {
        return [
            new ShipmentManualEventGrouperPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsReservationAggregationPluginInterface>
     */
    protected function getOmsReservationAggregationPlugins(): array
    {
        return [
            new ProductPackagingUnitOmsReservationAggregationPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    public function provideCommunicationLayerDependencies(Container $container): Container
    {
        $container = parent::provideCommunicationLayerDependencies($container);
        $container = $this->addTranslatorFacade($container);

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function addTranslatorFacade(Container $container): Container
    {
        $container->set(static::FACADE_TRANSLATOR, function (Container $container) {
            return $container->getLocator()->translator()->facade();
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function extendCommandPlugins(Container $container): Container
    {
        $container->extend(self::COMMAND_PLUGINS, function (CommandCollectionInterface $commandCollection) {
            $commandCollection->add(new SendOrderConfirmationPlugin(), 'Oms/SendOrderConfirmation');
            $commandCollection->add(new SendOrderShippedPlugin(), 'Oms/SendOrderShipped');
            $commandCollection->add(new StartReturnCommandPlugin(), 'Return/StartReturn');
            $commandCollection->add(new GenerateOrderInvoiceCommandPlugin(), 'Invoice/Generate');
            $commandCollection->add(new SendEventPaymentConfirmationPendingPlugin(), 'Payment/SendEventPaymentConfirmationPending');
            $commandCollection->add(new SendEventPaymentRefundPendingPlugin(), 'Payment/SendEventPaymentRefundPending');
            $commandCollection->add(new SendEventPaymentCancelReservationPendingPlugin(), 'Payment/SendEventPaymentCancelReservationPending');
            $commandCollection->add(new SendOrderStatusChangedMessagePlugin(), 'Order/RequestProductReviews');
            $commandCollection->add(new SubmitPaymentTaxInvoicePlugin(), 'TaxApp/SubmitPaymentTaxInvoice');

            // TODO-1: Add the PayCommandPlugin to the command collection and use the same name as in the State Machine definition
            // Hint-1: Use the same exact same string, including the slash.

            return $commandCollection;
        });

        return $container;
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function extendConditionPlugins(Container $container): Container
    {
        $container->extend(self::CONDITION_PLUGINS, function (ConditionCollectionInterface $conditionCollection) {

            // TODO-2: Add the IsAuthorizedConditionPlugin to the condition collection and use the same name as in the State Machine definition
            // Hint-1: Use the same exact same string, including the slash.
            // Hint-2: We use the variable `$conditionCollection` and the syntax is the same we used for adding the command.

            return $conditionCollection;
        });

        return $container;
    }

    /**
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\TimeoutProcessorPluginInterface>
     */
    protected function getTimeoutProcessorPlugins(): array
    {
        return [
            new InitiationTimeoutProcessorPlugin(),
        ];
    }

    /**
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsEventTriggeredListenerPluginInterface>
     */
    protected function getOmsEventTriggeredListenerPlugins(): array
    {
        return [
            new OrderRefundedEventListenerPlugin(),
        ];
    }

    /**
     * @param \Spryker\Zed\Kernel\Container $container
     *
     * @return \Spryker\Zed\Kernel\Container
     */
    protected function extendCommandPlugins(Container $container): Container
    {
        $container->extend(self::COMMAND_PLUGINS, function (CommandCollectionInterface $commandCollection) {
            $commandCollection->add(new SendOrderConfirmationPlugin(), 'Oms/SendOrderConfirmation');
            $commandCollection->add(new SendOrderShippedPlugin(), 'Oms/SendOrderShipped');
            $commandCollection->add(new StartReturnCommandPlugin(), 'Return/StartReturn');
            $commandCollection->add(new GenerateOrderInvoiceCommandPlugin(), 'Invoice/Generate');
            $commandCollection->add(new SendCapturePaymentMessageCommandPlugin(), 'Payment/Capture');
            $commandCollection->add(new SendRefundPaymentMessageCommandPlugin(), 'Payment/Refund');
            $commandCollection->add(new SendCancelPaymentMessageCommandPlugin(), 'Payment/Cancel');
            $commandCollection->add(new SendOrderStatusChangedMessagePlugin(), 'Order/RequestProductReviews');
            $commandCollection->add(new SubmitPaymentTaxInvoicePlugin(), 'TaxApp/SubmitPaymentTaxInvoice');

            return $commandCollection;
        });

        return $container;
    }
}
