/* 
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
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

IServ.FileDistribution = {};

IServ.FileDistribution.Form = IServ.register(function(IServ) {
    
    // store last value of title in this variable
    var lastTitle = '';
    
    function registerChangeHandler(e)
    {
        e.change(function() {
            lastTitle = $(this).val();
        });
    }
    
    function initialize()
    {
        $('.file-distribution-form-title').initialize(function() {
            registerChangeHandler($(this));
            
            if (lastTitle.length > 0) {
                $(this).val(lastTitle);
            }
        });
        
        $('.file-distribution-form-title').each(function() {
            var v = $(this).val();
            
            if (v.length > 0) {
                lastTitle = v;
            }
        })
    }
    
    // Public API
    return {
        init: initialize
    };

}(IServ)); // end of IServ module
