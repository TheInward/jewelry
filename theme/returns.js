(()=>{
 const form=document.getElementById('return-request-form');if(!form)return;
 const status=document.getElementById('return-form-status'),button=form.querySelector('[type=submit]'),details=form.elements.details;
 form.elements.reason.addEventListener('change',()=>{details.required=form.elements.reason.value==='other'});
 form.addEventListener('submit',async event=>{
  event.preventDefault();if(button.disabled)return;
  button.disabled=true;form.setAttribute('aria-busy','true');status.textContent='Sending your request…';
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),45000);
  try{
   const data=new FormData(form);
   const response=await fetch(seleneithReturns.ajax,{method:'POST',body:data,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'},signal:controller.signal});
   const payload=await response.json();
   if(!response.ok||!payload.success)throw Error(payload.data?.message||'We could not submit your request. Please try again or contact customer service.');
   if(!/^SR-[A-F0-9]{12}$/.test(payload.data?.reference||''))throw Error('We could not confirm your request. Please contact customer service before trying again.');
   document.getElementById('return-reference').textContent=payload.data.reference;
   form.hidden=true;const result=document.getElementById('return-result');result.hidden=false;result.focus();
  }catch(error){status.textContent=error.name==='AbortError'?'The request is taking longer than expected. Please try again; an identical request will not create a duplicate.':error.message;status.focus();}
  finally{clearTimeout(timer);button.disabled=false;form.removeAttribute('aria-busy');}
 });
})();
