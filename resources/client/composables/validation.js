export const required = (value) => (value !== null && value !== '') || 'Field is required';
export function length(min, max){
    return function(value){
        if(value === null){
            return true;
        }
        if(min !== null && value.length < min){
            return `Min ${min} characters`;
        }
        if(max !== null && value.length > max){
            return `Max ${max} characters`;
        }
       return true;
    }
}

const EMAIL_PATTERN = /^[a-z0-9]+(\.[_a-z0-9]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,15})$/;
export const email = value => EMAIL_PATTERN.test(value) || 'Invalid email';

const URL_PATTERN = /^(?:(?:(?:https?|ftp):)?\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})).?)(?::\d{2,5})?(?:[/?#]\S*)?$/i;
export const url = value => URL_PATTERN.test(value) || 'Invalid url';