/* 
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Binds title auto complete to enable form
 */
IServ.FileDistribution = {};

IServ.FileDistribution.Autocomplete = IServ.register(function (IServ) {
    "use strict";

    var thOptions = {
        minLength: 1,
        highlight: false
    };
    var thSource = {
    	remote: IServ.Routing.generate('fd_filedistribution_lookup') + '?query=%QUERY',
        templates: {
            suggestion: renderSuggestion
        }
    };
    var thExamSource = {
        remote: IServ.Routing.generate('fd_filedistribution_lookup_exam') + '?query=%QUERY',
        templates: {
            suggestion: renderSuggestion
        }
    };
    
    function registerCancelHandler()
    {
        // prevent requirement of input on cancel
        $('#iserv_crud_multi_select_actions_cancel').click(function() {
            $('#iserv_crud_multi_select_title').removeAttr('required');
            $('#iserv_crud_multi_select_isolation').removeAttr('required');
            $('#iserv_crud_multi_select_rpc_message').removeAttr('required');
            $('#iserv_crud_multi_select_exam_title').removeAttr('required');
            $('#iserv_crud_multi_select_inet_duration').removeAttr('required');
            return true;
        });
    }

    function initialize()
    {
        var element = $('#iserv_crud_multi_select_title');
        var examElement = $('#iserv_crud_multi_select_exam_title');
        
    	IServ.Autocomplete.make(element, thSource, thOptions);
        IServ.Autocomplete.make(examElement, thExamSource, thOptions);

        registerCancelHandler();
    }
    
    //see mail compose.js
    function renderSuggestion(data)
    {
        var icon;
        var label;
        var extra;
        
        switch(data.type) {
            case 'existing': 
                icon = 'pro-folder-heart'; 
                break;
            case 'running':
                icon = 'pro-play'; 
                break;
            default: 
                icon = 'question-sign';
        }
        
        label = '<h4 class="media-heading">' + data.label + '</h4>';
        extra = '<span class="text-muted">' + data.extra + '</span>';
        
        var suggestion = '<div class="media autocomplete-suggestion">';
        suggestion += '<div class="media-left">';
        suggestion += '<div class="icon">' + IServ.Icon.get(icon) + '</div>';
        suggestion += '</div>';
        suggestion += '<div class="media-body">';
        suggestion += label;
        suggestion += extra;
        suggestion += '</div>';
        suggestion += '</div>';
        
        return suggestion;
    }
    
    // Public API
    return {
        init: initialize
    }
}(IServ));
