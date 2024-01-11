<?php

namespace Dtgs\GoogleTagManager\Services;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Page;
use Symfony\Component\HttpFoundation\Request;

class GeneralTagsService
{

    private $systemConfigService;
    /**
     * @var EntityRepositoryInterface
     */
    private $languageRepository;

    public function __construct(SystemConfigService $systemConfigService, EntityRepositoryInterface $languageRepository)
    {
        $this->systemConfigService = $systemConfigService;
        $this->languageRepository = $languageRepository;
    }

    /**
     * @TODO!!
     *
     * @param Page $page
     * @param Context $context
     * @return array
     */
    public function getGeneralTags(Page $page, Context $context, Request $request)
    {
        $tags = array();

        $controller = $request->attributes->get('_controller');
        preg_match('/Controller\\\\(\w+)Controller::?(\w+)$/', $controller, $matches);
        $controller_name = (isset($matches[1])) ? ucfirst($matches[1]) : 'other';

        $meta = $page->getMetaInformation();
        $pageTitle = ($meta !== null) ? $meta->getMetaTitle() : '';

        $tags['pageTitle'] = $pageTitle;
        #pageSubCategory - empty
        $tags['pageSubCategory'] = '';
        #pageCategoryID - ID der aktuellen Kategorie
        $tags['pageCategoryID'] = '';
        #pageSubCategoryID
        $tags['pageSubCategoryID'] = '';
        #pageCountryCode
        $tags['pageCountryCode'] = $this->getLocaleCode($context);
        #pageLanguageCode;
        $tags['pageLanguageCode'] = $this->getLanguageCode($context);
        #pageVersion
        $tags['pageVersion'] = 1;
        #pageTestVariation
        $tags['pageTestVariation'] = '1';
        #pageValue
        $tags['pageValue'] = 1.0;
        #pageAttributes
        $tags['pageAttributes'] = '1';
        #pageCategory;
        $tags['pageCategory'] = $controller_name;

        return $tags;
    }

    /**
     * SW6 ready
     *
     * @param Request $request
     * @return array
     */
    public function getCustomGetParamsTags(Request $request) {

        $tags = array();
        $config = $this->systemConfigService->get('DtgsGoogleTagManagerSw6.config');

        if(!isset($config['customGetParams'])) return $tags;
        $custom_get_params = $config['customGetParams'];

        $custom_get_params = explode(';', $custom_get_params);

        foreach($custom_get_params as $param) {
            $param = explode(':', $param);

            if(count($param) == 2) {
                $param[0] = trim($param[0]);
                $param[1] = trim($param[1]);
                if($request->get($param[0])) {
                    $tags[$param[1]] = $request->get($param[0]);
                }
            }
        }

        return $tags;

    }

    /**
     * SW6 ready
     *
     * @param Request $request
     * @return array
     */
    public function getUtmTags(Request $request) {

        $tags = array();

        #UTMsource
        if($request->get('utm_source')) $tags['UTMsource'] = $request->get('utm_source');
        #UTMmedium
        if($request->get('utm_medium')) $tags['UTMmedium'] = $request->get('utm_medium');
        #UTMkeyword
        if($request->get('utm_term')) $tags['UTMkeyword'] = $request->get('utm_term');
        #UTMcampaign
        if($request->get('utm_campaign')) $tags['UTMcampaign'] = $request->get('utm_campaign');
        #UTMcontent
        if($request->get('utm_content')) $tags['UTMcontent'] = $request->get('utm_content');

        return $tags;

    }

    private function getLanguage(Context $context)
    {
        $languageId = $context->getLanguageId();
        $criteria = new Criteria([$languageId]);
        $criteria->addAssociation('locale');
        /** @var LanguageCollection $languageCollection */
        $languageCollection = $this->languageRepository->search($criteria, $context)->getEntities();

        return $languageCollection->get($languageId);
    }

    public function getLocaleCode(Context $context): string
    {
        $language = $this->getLanguage($context);
        if ($language === null) {
            return 'en-GB';
        }

        $locale = $language->getLocale();
        if (!$locale) {
            return 'en-GB';
        }

        return $locale->getCode();
    }

    public function getLanguageCode(Context $context): string
    {
        $language = $this->getLanguage($context);
        if ($language === null) {
            return 'en';
        }

        return $language->getName();
    }

}
