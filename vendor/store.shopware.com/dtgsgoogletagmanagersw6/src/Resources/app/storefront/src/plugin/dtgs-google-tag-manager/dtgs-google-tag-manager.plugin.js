import Plugin from 'src/plugin-system/plugin.class';
import { COOKIE_CONFIGURATION_UPDATE } from 'src/plugin/cookie/cookie-configuration.plugin';

import GtmAddToCartEvent from './events/add-to-cart.event';
import GtmRemoveFromCartEvent from './events/remove-from-cart.event';
import CookieStorageHelper from 'src/helper/storage/cookie-storage.helper';

export default class DtgsGoogleTagManagerPlugin extends Plugin
{
    init() {
        this.cookieEnabledName = 'dtgsAllowGtmTracking';

        this.handleCookieChangeEvent();

        if (!CookieStorageHelper.getItem(this.cookieEnabledName)) {
            return;
        }

        this.fireCookieConsentEvent();
        this.startGoogleTagManager();
    }

    fireCookieConsentEvent() {

        window.dataLayer.push({
            'event': 'cookieConsentGiven'
        });

    }

    startGoogleTagManager() {

        this.controllerName = window.controllerName;
        this.actionName = window.actionName;
        this.events = [];

        this.registerDefaultEvents();
        this.handleEvents();

    }

    handleEvents() {
        this.events.forEach(event => {
            if (!event.supports(this.controllerName, this.actionName)) {
                return;
            }

            event.execute();
        });
    }

    registerDefaultEvents() {
        this.registerEvent(GtmAddToCartEvent);
        this.registerEvent(GtmRemoveFromCartEvent);
    }

    registerEvent(event) {
        this.events.push(new event());
    }

    handleCookieChangeEvent() {
        document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, this.handleCookies.bind(this));
    }

    handleCookies(cookieUpdateEvent) {
        const updatedCookies = cookieUpdateEvent.detail;

        if (!updatedCookies.hasOwnProperty(this.cookieEnabledName)) {
            return;
        }

        if (updatedCookies[this.cookieEnabledName]) {
            this.fireCookieConsentEvent();
            this.startGoogleTagManager();
            return;
        }

        this.removeCookies();
        this.disableEvents();
    }

    removeCookies() {
        const allCookies = document.cookie.split(';');
        const gaCookieRegex = /^(_ga|_gat_UA$|_gid)/;

        allCookies.forEach(cookie => {
            const cookieName = cookie.split('=')[0].trim();
            if (!cookieName.match(gaCookieRegex)) {
                return;
            }

            CookieStorageHelper.removeItem(cookieName);
        });
    }

    disableEvents() {
        this.events.forEach(event => {
            event.disable();
        });
    }
}
