import {randomBytes} from 'node:crypto';
const base=process.env.ENROUTE_PREVIEW_URL||'http://39.106.108.70:8080/jewelry/';
const jar=new Map();
async function request(path,options={},depth=0){
 const response=await fetch(new URL(path,base),{...options,headers:{cookie:[...jar].map(([k,v])=>k+'='+v).join('; '),...options.headers},redirect:'manual',signal:AbortSignal.timeout(15000)});
 for(const raw of response.headers.getSetCookie()){const pair=raw.split(';')[0],index=pair.indexOf('=');jar.set(pair.slice(0,index),pair.slice(index+1));}
 if([301,302,303].includes(response.status)&&depth<6)return request(response.headers.get('location'),{},depth+1);
 return {status:response.status,html:await response.text()};
}
const email='codex-account-check-'+Date.now()+'@example.invalid';
const password='Aa9!'+randomBytes(16).toString('hex');
console.log('Test account:',email);
const publicPage=await request('');
if(publicPage.status!==200||!publicPage.html.includes('catalog-data')||publicPage.html.includes('name="password"'))throw Error('Public catalog access failed');
console.log('Anonymous catalog opens directly.');
const registerPage=await request('my-account/?create_account=1');
const registerNonce=registerPage.html.match(/name="woocommerce-register-nonce" value="([^"]+)"/)?.[1];
if(!registerNonce)throw Error('Registration form is missing');
const registered=await request('my-account/?create_account=1',{method:'POST',body:new URLSearchParams({first_name:'Preview',last_name:'Check',email,password,password_confirmation:password,agree_privacy:'1',register:'Create account','woocommerce-register-nonce':registerNonce})});
if(!registered.html.includes('woocommerce-MyAccount-navigation')||!registered.html.includes('customer-logout'))throw Error('Registration failed: '+registered.html.match(/woocommerce-error[\s\S]{0,600}/)?.[0]);
console.log('Registration succeeded; customer dashboard visible.');
for(const endpoint of ['orders','edit-address','edit-account']){const page=await request('my-account/'+endpoint+'/');if(page.status!==200||!page.html.includes('woocommerce-MyAccount-navigation'))throw Error(endpoint+' failed');console.log(endpoint+' accessible.');}
let dashboard=await request('my-account/');
const logout=dashboard.html.match(/href="([^"]*customer-logout[^\"]*)"/)?.[1]?.replaceAll('&amp;','&');
if(!logout)throw Error('Logout link missing');
const loggedOut=await request(logout);if(!loggedOut.html.includes('woocommerce-login-nonce'))throw Error('Logout failed');
console.log('Logout succeeded.');
// Start a clean anonymous session after checking logout: this lightweight
// test cookie jar does not model the multiple paths used by WordPress cookies.
jar.clear();
const freshLogin=await request('my-account/');
let nonce=freshLogin.html.match(/name="woocommerce-login-nonce" value="([^"]+)"/)?.[1];
const wrong=await request('my-account/',{method:'POST',body:new URLSearchParams({username:email,password:'incorrect-test-password',login:'Log in','woocommerce-login-nonce':nonce})});
if(wrong.html.includes('<nav class="woocommerce-MyAccount-navigation"')||!wrong.html.includes('woocommerce-error'))throw Error('Invalid password was not rejected: '+JSON.stringify({status:wrong.status,dashboard:wrong.html.includes('<nav class="woocommerce-MyAccount-navigation"'),notice:wrong.html.match(/woocommerce-notices-wrapper[\s\S]{0,600}/)?.[0]}));
console.log('Incorrect password rejected.');
const loginPage=await request('my-account/');nonce=loginPage.html.match(/name="woocommerce-login-nonce" value="([^"]+)"/)?.[1];
const loggedIn=await request('my-account/',{method:'POST',body:new URLSearchParams({username:email,password,login:'Log in','woocommerce-login-nonce':nonce})});
if(!loggedIn.html.includes('woocommerce-MyAccount-navigation'))throw Error('Email/password login failed');
console.log('Email/password login succeeded.');
const lost=await request('my-account/lost-password/');if(lost.status!==200||!lost.html.includes('user_login'))throw Error('Password recovery form missing');
console.log('Password recovery form available; external email delivery is not configured or verified.');
