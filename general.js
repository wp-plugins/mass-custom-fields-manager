function isValid(form) {
	ret=true;

	if ( empty(form.field.value) ) {	
		err='The "Field name" field is empty.';
		ret=false;
	}
	
	if( !ret )
		alert(err);
	
	return ret;
}

function toggleDelete() {
	var form=document.getElementById('frmMain');
	var ckd= (form.deletee.checked);

	form.new_value.disabled=ckd;
}

function empty( mixed_var ) {
    var key;
    
    if (mixed_var === "" ||
        mixed_var === 0 ||
        mixed_var === "0" ||
        mixed_var === null ||
        mixed_var === false ||
        mixed_var === undefined
    ){
        return true;
    }

    if (typeof mixed_var == 'object') {
        for (key in mixed_var) {
            return false;
        }
        return true;
    }

    return false;
}