<?php

declare(strict_types=1);

namespace MauticPlugin\LeuchtfeuerAuth0Bundle\Tests\EventListener;

use Mautic\CoreBundle\Event\CustomAssetsEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use MauticPlugin\LeuchtfeuerAuth0Bundle\EventListener\LoginFlashSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class LoginFlashSubscriberTest extends TestCase
{
    public function testLoginPagePinsTheGrowlToTheViewport(): void
    {
        $assets = new AssetsHelper(
            $this->createMock(Packages::class),
            $this->createMock(CoreParametersHelper::class)
        );
        $request = new Request();
        $request->attributes->set('_route', 'login');
        $stack = new RequestStack();
        $stack->push($request);

        (new LoginFlashSubscriber($stack))->onInjectCustomAssets(new CustomAssetsEvent($assets));

        self::assertStringContainsString(
            '#main-panel-flash-msgs .alert-growl-container{position:fixed;',
            $assets->getStyles()
        );
    }

    public function testOtherPagesAreLeftAlone(): void
    {
        $assets = new AssetsHelper(
            $this->createMock(Packages::class),
            $this->createMock(CoreParametersHelper::class)
        );
        $request = new Request();
        $request->attributes->set('_route', 'mautic_dashboard_index');
        $stack = new RequestStack();
        $stack->push($request);

        (new LoginFlashSubscriber($stack))->onInjectCustomAssets(new CustomAssetsEvent($assets));

        self::assertSame('', $assets->getStyles());
    }
}
