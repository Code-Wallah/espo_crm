define(['handlers/base'], function (BaseHandler) {

    return class extends BaseHandler {

        actionRefreshEmail() {
            // Show loading message
            Espo.Ui.notify('Checking for new emails...', 'info', 3000);
            
            // Disable the button temporarily to prevent double-clicks
            const buttonElement = this.view.$el.find('button[data-action="refreshEmail"]');
            buttonElement.prop('disabled', true).addClass('disabled');
            
            // Call the EmailSyncSimple API
            Espo.Ajax.postRequest('EmailSyncSimple/action/manualSync', {})
                .then(response => {
                    console.log('Email sync response:', response);
                    
                    if (response.success) {
                        const accountCount = response.results ? response.results.length : 0;
                        let message = 'Email sync completed successfully!';
                        
                        if (accountCount > 0) {
                            message = `Email sync queued for ${accountCount} account(s)`;
                        } else {
                            message = 'No email accounts found to sync';
                        }
                        
                        Espo.Ui.success(message);
                        
                        // Optionally refresh the email list after a short delay
                        setTimeout(() => {
                            if (this.view && this.view.collection) {
                                this.view.collection.fetch();
                            }
                        }, 2000);
                        
                    } else {
                        Espo.Ui.error('Email sync failed: ' + (response.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Email sync error:', error);
                    let errorMessage = 'Email sync failed';
                    
                    if (error.readyState === 0) {
                        errorMessage = 'Network error - please check your connection';
                    } else if (error.status === 403) {
                        errorMessage = 'Permission denied - check your access rights';
                    } else if (error.status === 404) {
                        errorMessage = 'Email sync service not found';
                    } else if (error.responseJSON && error.responseJSON.message) {
                        errorMessage = 'Email sync failed: ' + error.responseJSON.message;
                    }
                    
                    Espo.Ui.error(errorMessage);
                })
                .finally(() => {
                    // Re-enable the button
                    buttonElement.prop('disabled', false).removeClass('disabled');
                });
        }
    };

}); 