(()=>{
  const dialog=document.getElementById('signup-dialog');
  const trigger=document.getElementById('signup-trigger');
  if(!dialog||!trigger)return;
  const form=document.getElementById('signup-form');
  const status=document.getElementById('signup-status');
  const panel=document.getElementById('signup-form-panel');
  const result=document.getElementById('signup-result');
  const code=document.getElementById('signup-code');
  const button=form.querySelector('button'),email=form.elements.email,copy=document.getElementById('signup-copy');
  const t=(key)=>window.studyLocale?.strings?.[key]||key;
  let requestVersion=0;
  const open=()=>{
    requestVersion++;panel.hidden=false;result.hidden=true;code.textContent='';status.textContent='';copy.textContent=t('COPY');
    button.disabled=false;email.disabled=false;
    if(!dialog.open)dialog.showModal();email.focus();
  };
  trigger.addEventListener('click',open);
  document.getElementById('newsletter')?.addEventListener('submit',e=>{e.preventDefault();form.elements.email.value=e.target.querySelector('input').value;open()});
  document.getElementById('signup-close').addEventListener('click',()=>dialog.close());
  dialog.addEventListener('click',e=>{if(e.target===dialog)dialog.close()});
  dialog.addEventListener('close',()=>{requestVersion++;button.disabled=false;email.disabled=false});
  form.addEventListener('submit',async e=>{
    e.preventDefault();
    const version=++requestVersion;
    button.disabled=true;email.disabled=true;
    status.textContent=t('Creating your code…');
    const data=new URLSearchParams({action:'study_signup',nonce:studyConfig.nonce,email:form.elements.email.value});
    try{
      const response=await fetch(studyConfig.ajax,{method:'POST',body:data,credentials:'same-origin'});
      const payload=await response.json();
      if(version!==requestVersion)return;
      if(!response.ok||!payload.success)throw new Error(payload.data?.message||t('Please try again.'));
      code.textContent=payload.data.code;
      panel.hidden=true;
      result.hidden=false;
      if(payload.data.emailed)status.textContent=t('Your code was also sent by email.');
    }catch(error){if(version===requestVersion)status.textContent=error.message}
    finally{if(version===requestVersion){button.disabled=false;email.disabled=false}}
  });
  document.getElementById('signup-copy').addEventListener('click',async()=>{
    try{await navigator.clipboard.writeText(code.textContent);document.getElementById('signup-copy').textContent=t('COPIED')}catch(e){window.getSelection()?.selectAllChildren(code)}
  });
})();
