$(document).ready(function () {
    sessid_name = "PHPSESSID";

    data = document.cookie + ";";
    start = data.indexOf(sessid_name + "=");
    end = data.indexOf(";", start);
    value = data.substring(start + sessid_name.length + 1, end);

    forms = document.forms;
    for (var i = 0 ; i < forms.length ; i ++) {
        var form = forms[i];
        if (form.method == "post") {
            var childs = form.childNodes;
            for (var j = 0 ; j < childs.length ; j ++) {
                if(childs[j].name == 'code') {
                    form.removeChild(childs[j]);
                    break;
                }
            }
            var sessid = document.createElement("input");
            sessid.type = "hidden";
            sessid.name = 'code';
            sessid.value = value;
            form.appendChild(sessid);
        }
    }
});
