<?php

declare(strict_types=1);

namespace HDNET\Focuspoint\Controller;

use HDNET\Focuspoint\Service\WizardHandler\AbstractWizardHandler;
use HDNET\Focuspoint\Service\WizardHandler\File;
use HDNET\Focuspoint\Service\WizardHandler\FileReference;
use HDNET\Focuspoint\Service\WizardHandler\Group;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Wizard controller.
 */
class BackendController extends ActionController
{
    public function __construct(protected ModuleTemplateFactory $moduleTemplateFactory) {}

    /**
     * Returns the Module menu for the AJAX request.
     */
    public function wizardAction(ServerRequestInterface $request): ResponseInterface
    {
        $handler = $this->getCurrentHandler();
        $parameter = $request->getQueryParams();
        if (isset($parameter['save'])) {
            if (\is_object($handler)) {
                $xValue = (float) $parameter['xValue'];
                $yValue = (float) $parameter['yValue'];
                $handler->setCurrentPoint((int) ($xValue * 100), (int) ($yValue * 100));
            }

            return new RedirectResponse($parameter['P']['returnUrl']);
        }
        $saveArguments = [
            'save' => 1,
            'P' => [
                'returnUrl' => $parameter['P']['returnUrl'],
            ],
        ];

        $moduleTemplate = $this->moduleTemplateFactory->create($request);

        if (\is_object($handler)) {
            ArrayUtility::mergeRecursiveWithOverrule($saveArguments, $handler->getArguments());
            [$x, $y] = $handler->getCurrentPoint();

            $moduleTemplate->assignMultiple([
                'filePath' => $handler->getPublicUrl(),
                'currentLeft' => (($x + 100) / 2) . '%',
                'currentTop' => (($y - 100) / -2) . '%',
            ]);
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $moduleTemplate->assign('saveUri', (string) $uriBuilder->buildUriFromRoute('focuspoint', $saveArguments));

        return $moduleTemplate->renderResponse('Backend/Wizard');
    }

    /**
     * Get the current handler.
     */
    protected function getCurrentHandler(): ?AbstractWizardHandler
    {
        foreach ($this->getWizardHandler() as $handler) {
            /** @var AbstractWizardHandler $handler */
            if ($handler->canHandle()) {
                return $handler;
            }
        }

        return null;
    }

    protected function getWizardHandler(): iterable
    {
        yield GeneralUtility::makeInstance(File::class);

        yield GeneralUtility::makeInstance(FileReference::class);

        yield GeneralUtility::makeInstance(Group::class);
    }
}
