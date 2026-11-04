(()=>{
 document.addEventListener('click',event=>{
  const tab=event.target.closest('[data-account-switch]');
  if(tab&&document.querySelector('[data-account-panel="'+tab.dataset.accountSwitch+'"]')){event.preventDefault();document.querySelectorAll('[data-account-panel]').forEach(panel=>panel.hidden=panel.dataset.accountPanel!==tab.dataset.accountSwitch);history.replaceState(null,'',tab.href);document.querySelector('[data-account-panel]:not([hidden]) input')?.focus();}
  const toggle=event.target.closest('[data-password-target]');
  if(toggle){const input=document.getElementById(toggle.dataset.passwordTarget);const show=input.type==='password';input.type=show?'text':'password';const label=show?'Hide':'Show';toggle.textContent=studyLocale.strings[label]||label;toggle.setAttribute('aria-label',toggle.textContent);}
 });
})();
