jQuery(document).ready(function($){
    //Observer to adjust the media attachment modal window
    const attachmentPreviewObserver = new MutationObserver(function(mutations){
        // look through all mutations that just occurred
        for(let i = 0; i < mutations.length; i++){

            // look through all added nodes of this mutation
            for(let j = 0; j < mutations[i].addedNodes.length; j++){

                //get element
                const element = $(mutations[i].addedNodes[j]);

                //check if this is the attachment details section or if it contains the section
                //need this conditional as we need to trigger on initial modal open (creation) + next and previous navigation through media items
                let onAttachmentPage = false;
                if((element.hasClass('attachment-details')) || element.find('.attachment-details').length !== 0){
                    onAttachmentPage = true;
                }

                if(onAttachmentPage === true){
                    //find the URL value and update the details image
                    const urlLabel = element.find('span[data-setting="url"]');
                    if(urlLabel.length !== 0){
                        const value = urlLabel.find('input').val();
                        element.find('.details-image').attr('src', value);
                        element.find('.details-image').removeClass('icon');
                    }
                }
            }
        }
    });

    attachmentPreviewObserver.observe(document.body, {
        childList: true,
        subtree: true
    });
});