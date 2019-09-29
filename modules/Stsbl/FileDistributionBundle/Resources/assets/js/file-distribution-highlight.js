/*
 * The MIT License
 *
 * Copyright 2019 Felix Jacobi.
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
 * Enables highlighting of current host
 */
IServ.FileDistribution.Highlight = IServ.register(function (IServ) {
    "use strict";

    function initialize()
    {
        IServ.Loading.on('filedistribution.highlight');
        $.getJSON(IServ.Routing.generate('fd_filedistribution_lookup_hostname'), function (currentHostname)
        {
            IServ.Loading.off('filedistribution.highlight');
            console.log(currentHostname);
            if (currentHostname.id !== null) {
                $.initialize('#host_status_' + currentHostname.id, function () {
                    $(this).parent().parent().addClass('highlight');
                });
                $('#host_status_' + currentHostname.id).parent().parent().addClass('highlight');
            }
        });
    }

    // Public API
    return {
        init: initialize
    }
}(IServ));