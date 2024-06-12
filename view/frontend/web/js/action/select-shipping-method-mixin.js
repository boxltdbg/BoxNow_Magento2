/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */

define([
    "jquery",
    "Magento_Checkout/js/action/set-shipping-information",
    "mage/translate"
], function ($, setShippingInformationAction, $t) {
    "use strict";

    return function (target) {
        return function (shippingMethod) {
            //reset localstorage boxnow data
            const boxNowDetails = window.localStorage.getItem("boxnow"); //Assign old data to a variable
            const amastyEnabled = window.amastyEnabled;



            //define selectors
            const DEFAULT_SELECTORS = {
                contentContainer: 'div[class="contentContainer"]',
                nextStepButton: ".button.action.continue.primary",
                elementToAppendContent: $("form#co-shipping-method-form div").eq(-4),
                addressDiv: `<div class="boxnow_container"><h2 class="boxnow_details_header"></h2><div class="boxnow_details_address_text"></div><div class="boxnow_details_postcode_text"></div></div>`,
                boxNowRadioButton: 'input[value="boxnow_boxnow"]',
                boxNowRadioButtonSelected: 'input[checked="boxnow_boxnow"]',
            };

            const AMASTY_SELECTORS = {
                contentContainer: 'tr[class="boxnow_address"]',
                nextStepButton: ".action.primary.checkout.amasty",
                elementToAppendContent: ".amcheckout-items",
                boxNowRadioButton: 'input[value="boxnow_boxnow"]',
                boxNowRadioButtonSelected:
                    ".row.amcheckout-method.-selected > #label_carrier_boxnow_boxnow",
            };

            const IFRAME = `<iframe src=${window.boxNowIframeUrl} width="100%" height="500px"></iframe>`;

            //Set active selector
            let activeSelector = DEFAULT_SELECTORS;
            let { addressDiv } = DEFAULT_SELECTORS;

            //Check if amasty checkout is enabled and assign correct selectors
            if (amastyEnabled) {
                activeSelector = AMASTY_SELECTORS;
            }

            let {
                contentContainer,
                elementToAppendContent,
                nextStepButton,
            } = activeSelector;

            $(nextStepButton).prop("disabled", true);
            //Checking if boxnow radio button is clicked
            if (
                shippingMethod !== null &&
                shippingMethod["carrier_code"] === "boxnow"
            ) {
                //if iframe box exists do not add more iframes
                if ($(contentContainer).length > 0) {
                    $(nextStepButton).prop("disabled", true);
                    return target(shippingMethod);
                }

                //Disable Submit Button as no box is selected
                $(nextStepButton).prop("disabled", true);

                //We append our iframe to the dom - under BoxNow payment method

                if (amastyEnabled) {
                    $(elementToAppendContent).append(
                        `<tr class="boxnow_address"><td colspan="4">${addressDiv}</td></tr><tr class="boxnow_iframe"><td colspan="4">${IFRAME}</td></tr>`
                    );
                } else {
                    elementToAppendContent.append(`<div class="contentContainer">
                        ${addressDiv}
                        ${IFRAME}
                            </div>`);
                }

                //After selecting a box we capture message event
                //Captured data are inserted to our container
                window.addEventListener(
                    "message",
                    function (e) {
                        const {
                            boxnowLockerId,
                            boxnowLockerName,
                            boxnowLockerPostalCode,
                            boxnowLockerAddressLine1,
                            boxnowLockerAddressLine2,
                        } = e.data;
                        if (
                            boxnowLockerId &&
                            boxnowLockerName &&
                            boxnowLockerAddressLine1 &&
                            boxnowLockerPostalCode
                        ) {
                            //SAVE DATA TO LOCALSTORAGE
                            window.localStorage.setItem("boxnow", JSON.stringify(e.data));

                            //Show selected box information to user
                            $(".boxnow_details_header").text($t('Έχετε επιλέξει παραλαβή από: '));
                            $(".boxnow_details_address_text").text(
                                boxnowLockerAddressLine1 + $t(' TK:') + boxnowLockerPostalCode
                            );
                            $(".boxnow_container").css("display", "flex");

                            //Re-Enable Place Order Button
                            $(nextStepButton).prop("disabled", false);
                            //trigger shipping info observer and activate collectTotalsAfter observer
                            setShippingInformationAction();
                        }
                    },
                    false
                );
            } else {
                //When you click other radio buttons remove boxnow capture event and iframe
                window.removeEventListener("message", function (e) {}, false);
                $(nextStepButton).prop("disabled", false);
                $(contentContainer).remove();
                $(".boxnow_iframe").remove();
                if(amastyEnabled){
                    if (boxNowDetails) window.localStorage.removeItem("boxnow"); //If exists they are stale data - remove them
                }

                return target(shippingMethod);
            }

            return target(shippingMethod);
        };
    };
});
