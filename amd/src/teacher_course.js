function course_clicked(id){
    const errorTxt = $('#modcomp_error')[0];
    errorTxt.style.display = 'none';
    const params = `id=${id}`;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', './classes/inc/teacher_course.inc.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function(){
        if(this.status == 200){
            const text = JSON.parse(this.responseText);
            if(text['error']){
                errorTxt.innerText = text['error'];
                errorTxt.style.display = 'block';
            } else {
                if(text['return']){
                    const div = $('#course_content_div')[0];
                    div.innerHTML = text['return'];
                    const script = document.createElement('script');
                    script.src = './amd/min/progress_circle.min.js';
                    div.appendChild(script);
                }
            }
        }
    }
    xhr.send(params);
}
let canvas = document.querySelectorAll('.otjh-canvas');