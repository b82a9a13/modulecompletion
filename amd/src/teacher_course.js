function course_clicked(id){
    const errorTxt = document.getElementById('modcomp_error');
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
                    const div = document.getElementById('course_content_div');
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