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
                if(childs[j].nodeType == 1 && childs[j].name !== undefined) {
                    var name = childs[j].name;
                    var ac = new RegExp("^[a|c][1-9]+_code$", "i");
                    if(name == 'code' || name == 'formcode' || name.match(ac)) {
                        childs[j].value = value;
                        break;
                    }
                }
            }
        }
    }
});
