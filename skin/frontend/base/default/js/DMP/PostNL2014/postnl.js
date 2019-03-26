PostNL = {
    /*
     * Init
     *
     * Initialize the PostNL checkout.
     *
     */
    data: {},
    currentLocation: {},
    magentoRequest: null,

    DELIVERY_MORNING: 'morning',
    DELIVERY_NORMAL: 'standard',
    DELIVERY_NIGHT: 'avond',
    DELIVERY_SIGNED: 0,
    DELIVERY_ONLY_RECIPIENT: 0,

    setMagentoDataAndInit: function () {
        var ajaxOptions = {
            url: BASE_URL + 'postnl2014/checkout/info/?ran=' + Math.random(),
            success: function (response) {

                data = response.data;

                var address = data['address'];

                if (!address || !address['number']) {

                    PostNL.showMessage(
                        '<h4>Adresgegevens zijn niet ingevuld</h4>'
                    );
                    return;
                }

                price = [];

                price['default'] = '&#8364; ' + data.general['base_price'].toFixed(2).replace(".", ",");

                if (data.pickup['fee'] != 0) {
                    price['pickup'] = '&#8364; ' + data.pickup['fee'].toFixed(2).replace(".", ",");
                }

                if (data.delivery['signature_active'] == false) {
                    price['signed'] = 'disabled';
                } else if (data.delivery['signature_fee'] !== 0) {
                    price['signed'] = '+ &#8364; ' + data.delivery['signature_fee'].toFixed(2).replace(".", ",");
                }

                /**
                 * Exclude delivery types
                 */
                excludeDeliveryTypes = [];

                if (data.pickup['active'] == false) {
                    excludeDeliveryTypes.push('4');
                }

                var address = data['address'];

                if (null === address['full_street'] || address['postal_code'] === '' || address['number'] === ''){
                    PostNL.showMessage(
                        '<h3>Adres is nog niet volledig</h3>'
                    );
                    return;
                }

                if (address && (address['country'] === 'NL' || (address['country'] === 'BE'))) {

                    if (address['street']) {
                        postNLConfig = {
                            address: {
                                cc: address['country'],
                                street: address['street'],
                                number: address['number'],
                                postalCode: address['postal_code'].replace(/ /g, ""),
                                city: address['city']
                            },
                            txtWeekDays: [
                                'Zondag',
                                'Maandag',
                                'Dinsdag',
                                'Woensdag',
                                'Donderdag',
                                'Vrijdag',
                                'Zaterdag'
                            ],
                            translateENtoNL: {
                                'monday': 'maandag',
                                'tuesday': 'dindsag',
                                'wednesday': 'woensdag',
                                'thursday': 'donderdag',
                                'friday': 'vrijdag',
                                'saturday': 'zaterdag',
                                'sunday': 'zondag'
                            },
                            config: {
                                "apiBaseUrl": "https://api.myparcel.nl/",
                                "carrier": "1",

                                "priceMorningDelivery": data.morningDelivery['fee'],
                                "priceStandardDelivery":  data.general['base_price'],
                                "priceEveningDelivery": data.eveningDelivery['fee'],
                                "priceSignature": data.delivery['signature_fee'],
                                "pricePickup": data.pickup['fee'],
                                "pricePickupExpress": data.pickupExpress['fee'],
                                "priceOnlyRecipient": data.delivery['only_recipient_fee'],

                                "deliveryTitel":data.delivery['delivery_title'],
                                "deliveryMorningTitel":data.morningDelivery['morning_delivery_titel'],
                                "deliveryStandardTitel":data.delivery['standard_delivery_titel'],
                                "deliveryEveningTitel":data.eveningDelivery['eveningdelivery_titel'],
                                "pickupTitel": data.pickup['title'],
                                "signatureTitel": data.delivery['signature_title'],
                                "onlyRecipientTitel": data.delivery['only_recipient_title'],

                                "allowMondayDelivery": data.general['monday_delivery_active'],
                                "allowMorningDelivery": data.morningDelivery['active'],
                                "allowStandardDelivery": data.delivery['standard_delivery_active'],
                                "allowEveningDelivery": data.eveningDelivery['active'],
                                "allowSignature": data.delivery['signature_active'],
                                "allowOnlyRecipient": data.delivery['only_recipient_active'],
                                "allowPickupPoints": data.pickup['active'],

                                "dropOffDays": data.general['dropoff_days'],
                                "saturdayCutoffTime": data.general['saturday_cutoff_time'],
                                "cutoffTime": data.general['cutoff_time'],
                                "deliverydaysWindow": data.general['deliverydays_window'],
                                "dropoffDelay":data.general['dropoff_delay'],

                                "BelgiumDeliveryTitel": data.delivery['belgium_delivery_title'],
                                "BelgiumDeliveryStandardTitel": data.delivery['belgium_delivery_title'],
                            }
                        };

                        PostNL.init(postNLConfig);

                    }
                }
            }
        };

        if (null !== PostNL.magentoRequest) {
            PostNL.magentoRequest.abort();
        }
        PostNL.magentoRequest = jQuery.ajax(ajaxOptions);
    },

    init: function(externalData)
    {
        this.data = externalData;
        if (mypajQuery('#s_method_postnl_mailbox').length > 0) {
            return;
        }

        isMobile     = true;
        if(mypajQuery( window ).width() > 980 ) {
            isMobile = false;
        }

        /* Titels of the options*/
        mypajQuery('#mypa-delivery-title-section').hide();
        if (PostNL.data.config.allowMorningDelivery || PostNL.data.config.allowStandardDelivery || PostNL.data.config.allowEveningDelivery){
            mypajQuery('#mypa-delivery-title').html(PostNL.data.config.deliveryTitel);
            mypajQuery('#mypa-delivery-title-section').show();
        }
        if (PostNL.data.config.onlyRecipientTitel){
            mypajQuery('#mypa-only-recipient-titel').html(PostNL.data.config.onlyRecipientTitel);
        }
        if (PostNL.data.config.signatureTitel){
            mypajQuery('#mypa-signature-titel').html(PostNL.data.config.signatureTitel);
        }
        if (PostNL.data.config.pickupTitel){
            mypajQuery('#mypa-pickup-titel').html(PostNL.data.config.pickupTitel);
        }

        /* Prices */
        mypajQuery('#mypa-morning-delivery').html(PostNL.getPriceHtml(this.data.config.priceMorningDelivery));
        mypajQuery('#mypa-evening-delivery').html(PostNL.getPriceHtml(this.data.config.priceEveningDelivery));
        mypajQuery('#mypa-normal-delivery').html(PostNL.getPriceHtml(this.data.config.priceStandardDelivery));
        mypajQuery('#mypa-signature-price').html(PostNL.getPriceHtml(this.data.config.priceSignature));
        mypajQuery('#mypa-only-recipient-price').html(PostNL.getPriceHtml(this.data.config.priceOnlyRecipient));
        mypajQuery('#mypa-pickup-price').html(PostNL.getPriceHtml(this.data.config.pricePickup));

        /* Call delivery options */
        PostNL.callDeliveryOptions();

        /* Engage defaults */
        PostNL.hideDelivery();

        PostNL.bind();
    },

    getPriceHtml: function(priceOfDeliveryOption){

        if (!priceOfDeliveryOption) {
            var price = "";
        }

        if (parseFloat(priceOfDeliveryOption) >= 0){
            var price = '+ &euro; ' + Number(priceOfDeliveryOption).toFixed(2).replace(".", ",");
        }

        if (priceOfDeliveryOption && isNaN(parseFloat(priceOfDeliveryOption))){
            var price = priceOfDeliveryOption ;
        }

        return price;
    },

    setCurrentDeliveryOptions: function () {
        if (typeof PostNL.storeDeliveryOptions === 'undefined') {
            console.error('setCurrentDeliveryOptions() PostNL.storeDeliveryOptions === undefined');
            return;
        }

        var selectedDate 	= mypajQuery('#mypa-select-date').val();
        var selectDateKey 	= PostNL.storeDeliveryOptions.data.delivery[selectedDate]['time'];

        PostNL.hideMorningDelivery();
        PostNL.hideStandardDelivery();
        PostNL.hideEveningDelivery();

        mypajQuery.each(selectDateKey, function(key, value){

            if(value['price_comment'] == 'morning' && PostNL.data.config.allowMorningDelivery){
                var morningTitel = PostNL.data.config.deliveryMorningTitel;
                PostNL.getDeliveryTime(morningTitel,'morning', PostNL.data.config.deliveryMorningTitel, value['start'], value['end']);
                PostNL.showMorningDelivery();
            }
            if(value['price_comment'] == 'standard' && PostNL.data.config.allowStandardDelivery){
                var standardTitel = PostNL.data.config.deliveryStandardTitel;
                PostNL.getDeliveryTime(standardTitel,'standard', PostNL.data.config.deliveryStandardTitel, value['start'], value['end']);
                PostNL.showStandardDelivery();
            }
            if(value['price_comment'] == 'avond' && PostNL.data.config.allowEveningDelivery){
                var eveningTitel = PostNL.data.config.deliveryEveningTitel;
                PostNL.getDeliveryTime(eveningTitel, 'evening', PostNL.data.config.deliveryEveningTitel, value['start'], value['end'] );
                PostNL.showEveningDelivery();
            }

        });
    },
    getDeliveryTime: function (configDeliveryTitel, deliveryMoment, deliveryTitel, startTime, endTime) {
        startTime = startTime.replace(/(.*)\D\d+/, '$1');
        endTime = endTime.replace(/(.*)\D\d+/, '$1');

        mypajQuery('#mypa-'+deliveryMoment+'-titel').html(deliveryTitel);

        if (!configDeliveryTitel){
            mypajQuery('#mypa-'+deliveryMoment+'-titel').html(startTime + ' - ' + endTime);
        }

    },

    setCurrentLocation: function () {
        var locationId 			= mypajQuery('#mypa-pickup-location').val();
        this.currentLocation 	= this.getPickupByLocationId(PostNL.storeDeliveryOptions.data.pickup, locationId);

    },
    /*
     * Bind
     *
     * Bind actions to selectors.
     *
     */

    bind: function ()
    {
        mypajQuery('#mypa-submit').on('click', function(e)
        {
            e.preventDefault();
            PostNL.exportDeliveryOptionToWebshop();
        });

        /* show default delivery options and hide PostNL options */
        mypajQuery('#mypa-select-delivery').on('click', function(){
            PostNL.setCurrentDeliveryOptions();
            PostNL.showDelivery();
            PostNL.hidePickUpLocations();
        });

        /* hide default delivery options and show PostNL options */
        mypajQuery('#mypa-pickup-delivery').on('click', function(){
            PostNL.setCurrentLocation();
            PostNL.hideDelivery();
            PostNL.showPickUpLocations();
        });

        mypajQuery('#method-postnl-delivery-morning, #method-postnl-delivery-evening').on('click', function(){
            PostNL.defaultCheckCheckbox('mypa-only-recipient');
        });

        /* Mobile specific triggers */
        if(isMobile){
            mypajQuery('#mypa-show-location-details').on('click', function(){
                PostNL.setCurrentLocation();
                PostNL.showLocationDetails();
                PostNL.hideDelivery();
            });
        }

        /* Desktop specific triggers */
        else {
            mypajQuery('#mypa-show-location-details').on('mouseenter', function(){
                PostNL.setCurrentLocation();
                PostNL.showLocationDetails();
            });
        }

        mypajQuery('#mypa-location-details').on('click', function(){
            PostNL.hideLocationDetails();
        });

        mypajQuery('#method-postnl-normal').on('click', function(){
            PostNL.defaultCheckCheckbox('method-postnl-normal');
        });

        mypajQuery('#mypa-pickup-express').hide();  /* todo: move */


        mypajQuery('#mypa-pickup-delivery, #mypa-pickup-location').on('change', function(e){
            PostNL.setCurrentLocation();
            PostNL.toggleDeliveryOptions();
            PostNL.mapExternalWebshopTriggers();
        });

        mypajQuery('#mypa-select-date').on('change', function(e){
            PostNL.setCurrentDeliveryOptions();
            PostNL.mapExternalWebshopTriggers();
        });

        /* External webshop triggers */
        mypajQuery('#mypa-load').on('click', function () {

           PostNL.mapExternalWebshopTriggers()
        });
    },

    mapExternalWebshopTriggers: function () {
        PostNL.DELIVERY_SIGNED = 0;
        PostNL.DELIVERY_ONLY_RECIPIENT = 0;
        PostNL.removeStyleFromPrice();

        if (typeof PostNL.data.deliveryOptions === 'undefined') {
            console.info('PostNL.data.deliveryOptions not found');
            return;
        }

        /**
         * Morning delivery
         *
         */
        if (mypajQuery('#method-postnl-delivery-morning').prop('checked'))
        {
            mypajQuery('#s_method_postnl_morning').click();
            PostNL.DELIVERY_ONLY_RECIPIENT = 1;
            PostNL.addStyleToPrice('#mypa-morning-delivery, #mypa-only-recipient-price');

            /**
             * Signature
             */
            if (mypajQuery('#mypa-signature-selector').prop('checked'))
            {
                mypajQuery('#s_method_postnl_morning_signature').click();
                PostNL.DELIVERY_SIGNED = 1;
                PostNL.addStyleToPrice('#mypa-signature-price');
            }

            PostNL.addDeliveryToMagentoInput(PostNL.DELIVERY_MORNING);
            return;
        }

        /**
         * Normal delivery
         *
         */
        if (mypajQuery('#mypa-pickup-delivery').prop('checked') === false && mypajQuery('#method-postnl-normal').prop('checked'))
        {
            PostNL.addStyleToPrice('#mypa-normal-delivery');

            /**
             * Signature and only recipient
             */
            if (mypajQuery('#mypa-signature-selector').prop('checked') && mypajQuery('#mypa-only-recipient-selector').prop('checked'))
            {
                mypajQuery('#s_method_postnl_delivery_signature_and_only_recipient_fee').click();
                PostNL.DELIVERY_SIGNED = 1;
                PostNL.DELIVERY_ONLY_RECIPIENT = 1;
                PostNL.addStyleToPrice('#mypa-signature-price, #mypa-only-recipient-price');
            } else

            /**
             * Signature
             */
            if (mypajQuery('#mypa-signature-selector').prop('checked'))
            {
                mypajQuery('#s_method_postnl_delivery_signature').click();
                PostNL.DELIVERY_SIGNED = 1;
                PostNL.addStyleToPrice('#mypa-signature-price');

            } else

            /**
             * Only recipient
             */
            if (mypajQuery('#mypa-only-recipient-selector').prop('checked'))
            {
                mypajQuery('#s_method_postnl_delivery_only_recipient').click();
                PostNL.DELIVERY_ONLY_RECIPIENT = 1;
                PostNL.addStyleToPrice('#mypa-only-recipient-price');

            } else {
                mypajQuery('#s_method_postnl_flatrate, #s_method_postnl_tablerate').click();
            }

            PostNL.addDeliveryToMagentoInput(PostNL.DELIVERY_NORMAL);
            return;
        }

        /**
         * Evening delivery
         *
         */
        if (mypajQuery('#method-postnl-delivery-evening').prop('checked'))
        {
            mypajQuery('#s_method_postnl_evening').click();
            PostNL.DELIVERY_ONLY_RECIPIENT = 1;
            PostNL.addStyleToPrice('#mypa-evening-delivery, #mypa-only-recipient-price');

            /**
             * Signature
             */
            if (mypajQuery('#mypa-signature-selector').prop('checked'))
            {
                mypajQuery('#s_method_postnl_evening_signature').click();
                PostNL.DELIVERY_SIGNED = 1;
                PostNL.addStyleToPrice('#mypa-signature-price');
            }

            PostNL.addDeliveryToMagentoInput(PostNL.DELIVERY_NIGHT);
            return;
        }

        /**
         * Pickup
         *
         */
        if (mypajQuery('#mypa-pickup-delivery').prop('checked') || mypajQuery('#mypa-pickup-selector').prop('checked'))
        {
            /**
             * Early morning pickup
             */
            if (mypajQuery('#mypa-pickup-express-selector').prop('checked'))
            {
                mypajQuery('#s_method_postnl_pickup_express').click();
                PostNL.addPickupToMagentoInput('retailexpress');
                PostNL.addStyleToPrice('#mypa-pickup-express-price');
                return;
            }else{
                PostNL.addStyleToPrice('#mypa-pickup-price');
            }

            mypajQuery('#s_method_postnl_pickup').click();
            PostNL.addPickupToMagentoInput('retail');
        }
    },

    addPickupToMagentoInput: function (selectedPriceComment) {
        var locationId = mypajQuery('#mypa-pickup-location').val();
        var currentLocation = PostNL.getPickupByLocationId(PostNL.storeDeliveryOptions.data.pickup, locationId);

        var result = jQuery.extend({}, currentLocation);

        /* If retail; convert retailexpress to retail */
        if (selectedPriceComment === "retail") {
            result.price_comment = "retail";
        }

        mypajQuery('#mypa-input').val(JSON.stringify(result));
        if (typeof window.mypa != 'undefined') {
            window.mypa.fn.fnCheckout.saveShippingMethod();
        }
    },

    addStyleToPrice: function (chosenDelivery) {
        mypajQuery(chosenDelivery).addClass('mypa-bold-price');
    },

    removeStyleFromPrice: function (){
        mypajQuery('.mypa-delivery-option-table').find( "span" ).removeClass('mypa-bold-price');
    },

    addDeliveryToMagentoInput: function (deliveryMomentOfDay) {

        var deliveryDateId = mypajQuery('#mypa-select-date').val();

        var currentDeliveryData = PostNL.triggerDefaultOptionDelivery(deliveryDateId, deliveryMomentOfDay);

        if (currentDeliveryData !== null) {
            currentDeliveryData.signed = PostNL.DELIVERY_SIGNED;
            currentDeliveryData.only_recipient = PostNL.DELIVERY_ONLY_RECIPIENT
            mypajQuery('#mypa-input').val(JSON.stringify(currentDeliveryData));
        }
        if (typeof window.mypa != 'undefined') {
            window.mypa.fn.fnCheckout.saveShippingMethod();
        }
    },

    triggerDefaultOptionDelivery: function (deliveryDateId, deliveryMomentOfDay) {
        var dateArray = PostNL.data.deliveryOptions.data.delivery[deliveryDateId];
        var currentDeliveryData = null;

        mypajQuery.each(dateArray['time'], function(key, value) {
            if (value.price_comment === deliveryMomentOfDay) {
                currentDeliveryData = jQuery.extend({}, dateArray);
                currentDeliveryData['time'] = [value];
            }
        });

        if (currentDeliveryData === null) {
            mypajQuery('#mypa-only-recipient-selector').prop('disabled', false).prop('checked', false);
            mypajQuery('#method-postnl-normal').prop('checked', true);
            PostNL.mapExternalWebshopTriggers();
        }

        return currentDeliveryData;
    },

    /*
     * defaultCheckCheckbox
     *
     * Check the additional options that are required for certain delivery options
     *
     */
    defaultCheckCheckbox: function(selectedOption){
        if(selectedOption === 'mypa-only-recipient'){
            mypajQuery('#mypa-only-recipient-selector').prop('checked', true).prop({disabled: true});
            mypajQuery('#mypa-only-recipient-price').html('Inclusief');
        } else {
            mypajQuery('#mypa-only-recipient-selector').prop('checked', false).removeAttr("disabled");
            mypajQuery('#mypa-only-recipient-price').html(PostNL.getPriceHtml(this.data.config.priceOnlyRecipient));
        }
    },

    /*
     * toggleDeliveryOptions
     *
     * Shows and hides the display options that are valid for the recipient only and signature required pre-selectors
     *
     */

    toggleDeliveryOptions: function()
    {
        var isPickup	= mypajQuery('#mypa-pickup-delivery').is(':checked');
        mypajQuery('#mypa-pickup-selector').prop('checked', true);

        if(isPickup && this.currentLocation.price_comment === "retailexpress"){
            mypajQuery('#mypa-pickup-express-price').html(PostNL.getPriceHtml(this.data.config.pricePickupExpress));
            mypajQuery('#mypa-pickup-express').show();

        } else{
            mypajQuery('#mypa-pickup-express-selector').attr("checked", false);
            mypajQuery('#mypa-pickup-express').hide();

        }
    },


    /*
     * exportDeliverOptionToWebshop
     *
     * Exports the selected deliveryoption to the webshop.
     *
     */

    exportDeliveryOptionToWebshop: function()
    {
        var deliveryOption = "";
        var selected       = mypajQuery("#mypa-delivery-option-form").find("input[type='radio']:checked");
        if (selected.length > 0) {
            deliveryOption = selected.val();
        }

        /* XXX Send to appropriate webshop field */
    },


    /*
     * hideMessage
     *
     * Hides pop-up message.
     *
     */

    hideMessage: function()
    {
        mypajQuery('.mypa-message-model').hide();
        mypajQuery('#mypa-delivery-option-form').show();
    },

    /*
     * hideMessage
     *
     * Hides pop-up essage.
     *
     */

    showMessage: function(message)
    {
        PostNL.hideSpinner();
        mypajQuery('.mypa-message-model').show();
        mypajQuery('#mypa-message').html(message).show();
        mypajQuery('#mypa-delivery-option-form').hide();
        PostNL.hideSpinner();

    },

    /*
     * hideDelivery
     *
     * Hides interface part for delivery.
     *
     */

    hideDelivery: function()
    {
        PostNL.hideSignature();
        PostNL.hideOnlyRecipient();
        PostNL.hideMorningDelivery();
        PostNL.hideStandardDelivery();
        PostNL.hideEveningDelivery();

    },

    /*
     * showDelivery
     *
     * Shows interface part for delivery.
     *
     */

    showDelivery: function()
    {

        PostNL.hideStandardDelivery();
        if(this.data.config.allowStandardDelivery){
            PostNL.showStandardDelivery();
        }

        PostNL.hideSignature();
        if(this.data.config.allowSignature){
            PostNL.showSignature();
        }

        PostNL.hideOnlyRecipient();
        if(this.data.config.allowOnlyRecipient){
            PostNL.showOnlyRecipient();
        }

        if(PostNL.data.address.cc === 'BE'){
            mypajQuery('#mypa-delivery-title').html(PostNL.data.config.BelgiumdeliveryTitel);
            mypajQuery('#mypa-delivery-date-text,#mypa-delivery-date-select').hide();
        }
    },

    /*
     * showSpinner
     *
     * Shows the PostNL spinner.
     *
     */

    showSpinner: function()
    {
        mypajQuery('.mypa-message-model').hide();
        mypajQuery('#mypa-spinner-model').show();
    },


    /*
     * hideSpinner
     *
     * Hides the PostNL spinner.
     *
     */

    hideSpinner: function()
    {
        mypajQuery('#mypa-spinner-model').hide();
    },

    showMorningDelivery: function()
    {
        mypajQuery('#method-postnl-delivery-morning-section').show();
    },

    hideMorningDelivery: function()
    {
        mypajQuery('#method-postnl-delivery-morning-section').hide();
    },


    showStandardDelivery: function()
    {
        mypajQuery('#method-postnl-delivery-standard-section').show();
        mypajQuery('#mypa-pre-selectors-' +      this.data.address.cc.toLowerCase()).show();
        mypajQuery('#mypa-delivery-selectors-' + this.data.address.cc.toLowerCase()).show();
        mypajQuery('#mypa-delivery, #mypa-normal-delivery, #mypa-delivery-date-select').show();
        mypajQuery('#mypa-delivery').parent().parent().show();
    },

    hideStandardDelivery: function()
    {
        mypajQuery('#method-postnl-delivery-standard-section').hide();
        mypajQuery('#mypa-delivery-date-select, #mypa-pre-selectors-nl, #mypa-delivery, #mypa-normal-delivery').hide();
        mypajQuery('#mypa-delivery').parent().parent().hide();


    },

    showEveningDelivery: function()
    {
        mypajQuery('#method-postnl-delivery-evening-section').show();
    },

    hideEveningDelivery: function()
    {
        mypajQuery('#method-postnl-delivery-evening-section').hide();
    },

    showSignature: function()
    {
        mypajQuery('.mypa-extra-delivery-option-signature, #mypa-signature-price').show();
    },

    hideSignature: function()
    {
        mypajQuery('.mypa-extra-delivery-option-signature, #mypa-signature-price').hide();
    },

    showOnlyRecipient: function()
    {
        mypajQuery('#mypa-only-recipient, #mypa-only-recipient-price').parent().show();
    },

    hideOnlyRecipient: function()
    {
        mypajQuery('#mypa-only-recipient, #mypa-only-recipient-price').parent().hide();
    },

    /*
     * dateToString
     *
     * Convert api date string format to human readable string format
     *
     */

    dateToString: function(apiDate)
    {
        var deliveryDate 	= apiDate;
        var dateArr      	= deliveryDate.split('-');
        var dateObj      	= new Date(dateArr[0],dateArr[1]-1,dateArr[2]);
        var day				= ("0" + (dateObj.getDate())).slice(-2);
        var month        	= ("0" + (dateObj.getMonth() + 1)).slice(-2);

        return this.data.txtWeekDays[dateObj.getDay()] + " " + day + "-" + month + "-" + dateObj.getFullYear();
    },

    /*
     * showDeliveryDates
     *
     * Show possible delivery dates.
     *
     */

    showDeliveryDates: function()
    {
        var html = "";
        var deliveryWindow = parseInt(PostNL.data.config.deliverydaysWindow);

        mypajQuery.each(PostNL.data.deliveryOptions.data.delivery, function(key, value){
            html += '<option value="' + key + '">' + PostNL.dateToString(value.date) + ' </option>\n';
        });

        /* Hide the day selector when the value of the deliverydaysWindow is 0*/
        if (deliveryWindow === 0){
            mypajQuery('#mypa-select-date').hide();
        }

        /* When deliverydaysWindow is 1, hide the day selector and show a div to show the date */
        if (deliveryWindow === 1){
            mypajQuery('#mypa-select-date').hide();
            mypajQuery('#mypa-delivery-date-text').show();
        }

        /* When deliverydaysWindow > 1, show the day selector */
        if (deliveryWindow > 1){
            mypajQuery('#mypa-select-date').show();
        }

        mypajQuery('#mypa-select-date, #mypa-date').html(html);
    },

    hideDeliveryDates: function()
    {
        mypajQuery('#mypa-delivery-date-text').hide();
    },

    /*
     * clearPickupLocations
     *
     * Clear pickup locations and show a non-value option.
     *
     */

    clearPickUpLocations: function()
    {
        var html = '<option value="">---</option>';
        mypajQuery('#mypa-pickup-location').html(html);
    },


    /*
     * hidePickupLocations
     *
     * Hide the pickup location option.
     *
     */

    hidePickUpLocations: function()
    {
        if(!PostNL.data.config.allowPickupPoints) {
            mypajQuery('#mypa-pickup-location-selector').hide();
        }

        mypajQuery('#mypa-pickup-options, #mypa-pickup, #mypa-pickup-express').hide();

    },


    /*
     * showPickupLocations
     *
     * Shows possible pickup locations, from closest to furdest.
     *
     */

    showPickUpLocations: function()
    {
        if (PostNL.data.config.allowPickupPoints && typeof PostNL.data.deliveryOptions !== 'undefined') {

            var html = "";
            mypajQuery.each(PostNL.data.deliveryOptions.data.pickup, function (key, value) {
                var distance = parseFloat(Math.round(value.distance) / 1000).toFixed(1);
                html += '<option value="' + value.location_code + '">' + value.location + ', ' + value.street + ' ' + value.number + ", " + value.city + " (" + distance + " km) </option>\n";
            });
            mypajQuery('#mypa-pickup-location').html(html).prop("checked", true);
            mypajQuery('#mypa-pickup-location-selector, #mypa-pickup-options, #mypa-pickup').show();
        }
    },

    /*
     * hideLocationDetails
     *
     * Hide the detailed information pop-up for selected location.
     *
     */

    hideLocationDetails: function()
    {
        mypajQuery('#mypa-delivery-option-form').show();
        mypajQuery('#mypa-location-details').hide();
    },

    /*
     * showLocationDetails
     *
     * Shows the detailed information pop-up for the selected pick-up location.
     */

    showLocationDetails: function()
    {
        var html       		= "";
        var locationId 		= mypajQuery('#mypa-pickup-location').val();

        var currentLocation = PostNL.getPickupByLocationId(PostNL.storeDeliveryOptions.data.pickup, locationId);
        var startTime = currentLocation.start_time;

        /* Strip seconds if present */
        if(startTime.length > 5){
            startTime = startTime.slice(0,-3);
        }

        html += '<svg  class="svg-inline--fa mypa-fa-times fa-w-12" aria-hidden="true" data-prefix="fas" data-icon="times" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" data-fa-i2svg=""><path fill="currentColor" d="M323.1 441l53.9-53.9c9.4-9.4 9.4-24.5 0-33.9L279.8 256l97.2-97.2c9.4-9.4 9.4-24.5 0-33.9L323.1 71c-9.4-9.4-24.5-9.4-33.9 0L192 168.2 94.8 71c-9.4-9.4-24.5-9.4-33.9 0L7 124.9c-9.4 9.4-9.4 24.5 0 33.9l97.2 97.2L7 353.2c-9.4 9.4-9.4 24.5 0 33.9L60.9 441c9.4 9.4 24.5 9.4 33.9 0l97.2-97.2 97.2 97.2c9.3 9.3 24.5 9.3 33.9 0z"></path></svg>'
        html += '<span class="mypa-pickup-location-details-location"><h4>' + currentLocation.location  + '</h4></span>';
        html += '<svg class="mypa-postnl-logo" aria-hidden="true" data-prefix="PostNL" data-icon="PostNL" role="img" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve"><image xlink:href="data:image/png;base64,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"/></svg>';
        html += '<span class="mypa-pickup-location-details-street">' + currentLocation.street + '&nbsp;' + this.currentLocation.number + '</span>';
        html += '<span class="mypa-pickup-location-details-city">' + currentLocation.postal_code + '&nbsp;' + currentLocation.city + '</span>';

        if(currentLocation.phone_number){
            html += '<span class="mypa-pickup-location-details-phone">' + currentLocation.phone_number  + '</span>'
        }
        html += '<span class="mypa-pickup-location-details-time">Ophalen vanaf:&nbsp;' + startTime + '</span>'
        html += '<h3>Openingstijden</h3>';

        mypajQuery.each(
            currentLocation.opening_hours, function(weekday, value){
                html += '<span class="mypa-pickup-location-details-day">' + PostNL.data.translateENtoNL[weekday] + "</span> ";

                if(value[0] === undefined ){
                    html +=  '<span class="mypa-time">Gesloten</span>';
                }

                mypajQuery.each(value, function(key2, times){
                    html +=  '<span class="mypa-time">' + times + "</span>";
                });
                html += "<br>";
            });
        mypajQuery('#mypa-delivery-option-form').hide();
        mypajQuery('#mypa-location-details').html(html).show();
    },

    /*
     * getPickupByLocationId
     *
     * Find the location by id and return the object.
     *
     */
    getPickupByLocationId: function (obj, locationId) {
        var object;

        mypajQuery.each(obj, function (key, info) {
            if (info.location_code === locationId) {
                object = info;
                return false;
            };
        });

        return object;
    },

    /*
     * retryPostalcodeHouseNumber
     *
     * After detecting an unrecognised postcal code / house number combination the user can try again.
     * This function copies the newly entered data back into the webshop forms.
     *
     */

    retryPostalcodeHouseNumber: function()
    {
        this.data.address.postalCode = mypajQuery('#mypa-error-postcode').val();
        this.data.address.number = mypajQuery('#mypa-error-number').val();
        PostNL.callDeliveryOptions();
        mypajQuery('#mypa-select-delivery').click();
    },

    /*
     * showFallBackDelivery
     *
     * If the API call fails and we have no data about delivery or pick up options
     * show the customer an "As soon as possible" option.
     */

    showFallBackDelivery: function()
    {
        PostNL.hideSpinner();
        PostNL.hideDelivery();
        mypajQuery('#mypa-select-date, #method-postnl-normal').hide();
        mypajQuery('.mypa-is-pickup-element').hide();
        mypajQuery('#mypa-select-delivery-titel').html('Zo snel mogelijk bezorgen');
    },


    /*
     * showRetru
     *
     * If a customer enters an unrecognised postal code housenumber combination show a
     * pop-up so they can try again.
     */

    showRetry: function()
    {
        PostNL.showMessage(
            '<h4>Huisnummer/postcode combinatie onbekend</h4>' +
            '<div class="mypa-full-width mypa-error">'+
            '<label for="mypa-error-postcode">Postcode</label>' +
            '<input type="text" name="mypa-error-postcode" id="mypa-error-postcode" value="'+ PostNL.data.address.postalCode +'">' +
            '</div><div class="mypa-full-width mypa-error">' +
            '<label for="mypa-error-number">Huisnummer</label>' +
            '<input type="text" name="mypa-error-number" id="mypa-error-number" value="'+ PostNL.data.address.number +'">' +
            '<br><div id="mypa-error-try-again" class="button btn"><span>Opnieuw</span></div>' +
            '</div>'
        );

        /* remove trigger that closes message */
        mypajQuery('#mypa-message').off('click');

        /* bind trigger to new button */
        mypajQuery('#mypa-error-try-again').on('click', function(){
            PostNL.retryPostalcodeHouseNumber();
        });
    },


    /*
     * callDeliveryOptions
     *
     * Calls the PostNL API to retrieve the pickup and delivery options for given house number and
     * Postal Code.
     *
     */

    callDeliveryOptions: function()
    {
        PostNL.showSpinner();
        PostNL.clearPickUpLocations();

        var cc 				= this.data.address.cc;
        var postalCode 		= this.data.address.postalCode;
        var number 			= this.data.address.number;
        var city 			= this.data.address.city;

        if (!number || !postalCode){
            PostNL.showMessage(
                '<h3>Adres gegevens zijn niet ingevuld</h3>'
            );
            return;
        }
        if (cc === "BE") {
            var numberExtra 	= this.data.address.numberExtra;
            var street 			= this.data.address.street;
        }

        if(numberExtra){
            number = number + numberExtra;
        }

        /* Check if the deliverydaysWindow == 0 and hide the select input*/
        this.deliveryDaysWindow = this.data.config.deliverydaysWindow;

        if(this.deliveryDaysWindow <= 0){
            this.deliveryDaysWindow = 1;
        }

        /* Make the api request */
        mypajQuery.get(this.data.config.apiBaseUrl + "delivery_options",
            {
                cc           			:this.data.address.cc,
                postal_code  			:postalCode,
                number       			:number,
                city					:city,
                carrier      			:this.data.config.carrier,
                dropoff_days			:this.data.config.dropOffDays,
                monday_delivery			:this.data.config.allowMondayDelivery,
                deliverydays_window		:this.deliveryDaysWindow,
                cutoff_time 			:this.data.config.cutoffTime,
                dropoff_delay			:this.data.config.dropoffDelay
            })
            .done(function(response){

                PostNL.data.deliveryOptions = response;
                if(response.errors){
                    mypajQuery.each(response.errors, function(key, value){
                        /* Postalcode housenumber combination not found or not recognised. */
                        if(value.code == '3212' || value.code == '3505'){
                            PostNL.showRetry();
                        }

                        /* Any other error */
                        else {
                            PostNL.showFallBackDelivery();
                        }
                    });
                }

                /* No errors */
                else {
                    PostNL.hideMessage();
                    PostNL.showPickUpLocations();
                    PostNL.showDeliveryDates();
                    if(PostNL.data.deliveryOptions.data.delivery.length <= 0 ){
                        PostNL.hideDeliveryDates();
                    }
                    PostNL.storeDeliveryOptions = response;
                }
                PostNL.hideSpinner();
            })
            .fail(function(){
                PostNL.showFallBackDelivery();
            })
            .always(function(){
                mypajQuery('#mypa-select-delivery, #method-postnl-normal').click();

                if (!PostNL.data.config.allowMorningDelivery && !PostNL.data.config.allowStandardDelivery && !PostNL.data.config.allowEveningDelivery){
                    mypajQuery('#mypa-pickup-delivery, #mypa-pickup-selector').click();
                }

                PostNL.mapExternalWebshopTriggers();

                if (typeof window.mypa != 'undefined') {
                    window.mypa.fn.fnCheckout.paymentRefresh();
                }
            });
    }
}
