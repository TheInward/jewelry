(()=>{
 document.addEventListener('click',event=>{
  const tab=event.target.closest('[data-account-switch]');
  if(tab){event.preventDefault();document.querySelectorAll('[data-account-panel]').forEach(panel=>panel.hidden=panel.dataset.accountPanel!==tab.dataset.accountSwitch);history.replaceState(null,'',tab.href);document.querySelector('[data-account-panel]:not([hidden]) input')?.focus();}
  const toggle=event.target.closest('[data-password-target]');
  if(toggle){const input=document.getElementById(toggle.dataset.passwordTarget);const show=input.type==='password';input.type=show?'text':'password';toggle.textContent=show?'Hide':'Show';toggle.setAttribute('aria-label',show?'Hide password':'Show password');}
 });
})();
