define('custom:views/opportunity/record/edit', ['crm:views/opportunity/record/edit'], function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            // Listen for account field changes
            this.listenTo(this.model, 'change:accountId', this.updateOpportunityName);
            this.listenTo(this.model, 'change:accountName', this.updateOpportunityName);
        },

        updateOpportunityName: function () {
            var accountName = this.model.get('accountName');
            
            if (accountName) {
                var opportunityName = accountName + ' - Opportunity';
                
                // Set the name without triggering save validation
                this.model.set('name', opportunityName, {silent: false});
                
                // Update the field view if it exists
                var nameFieldView = this.getFieldView('name');
                if (nameFieldView) {
                    nameFieldView.reRender();
                }
            }
        }

    });

}); 