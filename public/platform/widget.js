(() => {
  'use strict';
  if (document.getElementById('sn-widget')) return;
  const base = document.querySelector('meta[name="platform-base"]')?.content || '/';
  const url = path => new URL(path, base).href;
  const root = document.createElement('div'); root.id = 'sn-widget';
  const css = document.createElement('link'); css.rel = 'stylesheet'; css.href = url('platform/widget.css'); document.head.append(css);
  root.innerHTML = `<button class="sn-launch" aria-label="เปิดผู้ช่วยร้านค้า" aria-expanded="false">✦ ผู้ช่วยร้านค้า</button><section class="sn-chat" hidden aria-label="สนทนากับร้านค้า"><header><strong>SkyNext Assistant</strong><button class="sn-close" aria-label="ปิดผู้ช่วย">×</button></header><div class="sn-history" aria-live="polite"></div><form class="sn-send"><label class="sn-check"><input type="checkbox" name="consent" checked required> ยินยอมให้บันทึกการสนทนาเพื่อให้บริการ</label><label class="sn-check sn-ai" hidden><input type="checkbox" name="ai_consent" checked> ยินยอมส่งข้อความให้ AI (DeepSeek) ช่วยตอบ</label><div class="sn-row"><input name="message" aria-label="ข้อความถึงร้านค้า" placeholder="สอบถามสินค้า หรือพิมพ์ แอดมิน" required maxlength="2000"><button>ส่ง</button></div></form><details><summary>ฝากข้อมูลให้ทีมงานติดต่อกลับ</summary><form class="sn-lead"><input name="name" aria-label="ชื่อสำหรับติดต่อกลับ" placeholder="ชื่อ" required maxlength="120"><input name="contact" aria-label="ช่องทางติดต่อกลับ" placeholder="เบอร์โทรหรืออีเมล" required maxlength="200"><label class="sn-check"><input type="checkbox" name="consent" required> ยินยอมให้ร้านติดต่อกลับตามคำขอ</label><button>ส่งข้อมูลติดต่อ</button></form></details><p class="sn-status" role="status"></p></section>`;
  document.body.append(root);
  const syncToken = result => {
    if(!result.csrf || !result.csrf_name) return;
    for(const input of document.querySelectorAll('input[type="hidden"]')) if(input.name===result.csrf_name) input.value=result.csrf;
  };
  // Serialize widget writes to avoid racing rotating session CSRF tokens.
  let pending=Promise.resolve();
  const request = async (path,data) => {
    const boot = await fetch(url('api/platform/bootstrap'), {credentials:'same-origin'}).then(r=>r.json()); syncToken(boot);
    if (!boot.ready) throw new Error('ระบบร้านค้ายังไม่พร้อมใช้งาน');
    const response = await fetch(url('api/platform/' + path), data ? {method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':boot.csrf,'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(data)} : {credentials:'same-origin'});
    const result = await response.json(); syncToken(result); if(!response.ok) throw new Error(result.error || 'กรุณารีเฟรชหน้าแล้วลองใหม่'); return result;
  };
  const api=(path,data)=>{const task=pending.then(()=>request(path,data));pending=task.catch(()=>{});return task;};
  document.addEventListener('submit',event=>{
    if(event.target.closest('#sn-widget')) return;
    // Refresh the form's token immediately before a normal form submission.
    const form=event.target;if(!(form instanceof HTMLFormElement)||form.method.toLowerCase()!=='post')return;
    if(form.dataset.snReady==='1'){delete form.dataset.snReady;return;}
    event.preventDefault();const submitter=event.submitter;
    pending.then(()=>fetch(url('api/platform/bootstrap'),{credentials:'same-origin'})).then(r=>r.json()).then(result=>{syncToken(result);form.dataset.snReady='1';form.requestSubmit(submitter||undefined);}).catch(()=>{form.dataset.snReady='1';form.requestSubmit(submitter||undefined);});
  });
  let widgetConfig = null;
  const status = root.querySelector('.sn-status');
  const showMessages = async () => {
    try {
      const data = await api('chat'); const list = root.querySelector('.sn-history'); const last = data.messages.at(-1)?.id;
      if(list.dataset.last === String(last)) return; list.replaceChildren();
      if(!data.messages.length) { 
        const welcome=document.createElement('p');
        welcome.textContent = (widgetConfig && (widgetConfig.web_widget_welcome || widgetConfig.bot_welcome_msg)) || 'สวัสดีค่ะ สอบถามสินค้า หรือฝากข้อมูลให้ติดต่อกลับได้ที่นี่';
        list.append(welcome); 
      }
      for (const m of data.messages) {const el=document.createElement('p');el.className=m.direction==='in'?'sn-own':'sn-reply';el.textContent=m.body;list.append(el);}
      list.dataset.last=String(last);list.scrollTop=list.scrollHeight;status.textContent=data.mode==='human'?'เจ้าหน้าที่จะตอบกลับในกล่องข้อความนี้':'';
    } catch(e) { status.textContent=e.message; }
  };
  const panel=root.querySelector('.sn-chat'); const launch=root.querySelector('.sn-launch');
  launch.addEventListener('click',()=>{panel.hidden=!panel.hidden;launch.setAttribute('aria-expanded',String(!panel.hidden));if(!panel.hidden)showMessages();});
  root.querySelector('.sn-close').addEventListener('click',()=>{panel.hidden=true;launch.setAttribute('aria-expanded','false');});
  root.querySelector('.sn-send').addEventListener('submit',async event=>{
    event.preventDefault();event.stopPropagation();const f=event.currentTarget;const b=f.querySelector('button');b.disabled=true;status.textContent='กำลังส่ง…';
    try {await api('chat',{message:f.elements.message.value,consent:f.elements.consent.checked?'1':'0',ai_consent:f.elements.ai_consent.checked?'1':'0'});f.elements.message.value='';delete root.querySelector('.sn-history').dataset.last;await showMessages();}
    catch(e){status.textContent=e.message;}finally{b.disabled=false;}
  });
  root.querySelector('.sn-lead').addEventListener('submit',async event=>{
    event.preventDefault();event.stopPropagation();const f=event.currentTarget;const b=f.querySelector('button');b.disabled=true;
    try{await api('lead',{name:f.elements.name.value,contact:f.elements.contact.value,consent:f.elements.consent.checked?'1':'0'});f.reset();await showMessages();status.textContent='รับข้อมูลแล้ว ทีมงานจะติดต่อกลับ';}catch(e){status.textContent=e.message;}finally{b.disabled=false;}
  });
  setInterval(()=>{if(!panel.hidden&&!document.hidden)showMessages();},8000);
  if(document.querySelector('meta[name="platform-mirror"]')){
    const dock=document.createElement('nav');dock.className='sn-dock';dock.setAttribute('aria-label','ร้านค้าและสมาชิก');
    for(const [path,label] of [['shop','เลือกซื้อสินค้า'],['cart','ตะกร้าสินค้า'],['account','บัญชีของฉัน']]){const a=document.createElement('a');a.href=url(path);a.textContent=label;dock.append(a);}root.append(dock);
    fetch(url('api/platform/catalog-map')).then(r=>r.ok?r.json():null).then(data=>{
      if(!data?.products)return;const byPath=new Map(data.products.map(p=>[decodeURIComponent(p.source_path).replace(/\/$/,''),p]));
      const prefix=new URL(base).pathname.replace(/\/$/,'');const strip=path=>prefix&&path.startsWith(prefix)?path.slice(prefix.length):path;
      for(const a of document.querySelectorAll('a[href]')){
        let path;try{const target=new URL(a.href);if(target.origin!==new URL(base).origin)continue;path=strip(decodeURIComponent(target.pathname).replace(/\/$/,''));}catch{continue;}
        if(a.getAttribute('href')?.includes('#woo-cart-panel')){a.href=url('cart');continue;}
        if(a.getAttribute('href')?.includes('#account-modal')){a.href=url('account');continue;}
        if(path==='/products'){a.href=url('shop');continue;}
        if(path.startsWith('/product-category/')){a.href=url('shop')+'?category='+encodeURIComponent(path.slice('/product-category/'.length).split('/').join(' / '));continue;}
        const p=byPath.get(path);if(!p)continue;
        if(!p.active){a.closest('li.product')?.setAttribute('hidden','hidden');a.href=url('shop');continue;}
        a.href=url('shop/product/'+p.id);
        const price=a.closest('li.product')?.querySelector('.price');if(price)price.textContent=new Intl.NumberFormat('th-TH',{style:'currency',currency:'THB'}).format(p.price_satang/100);
      }
      const current=byPath.get(strip(decodeURIComponent(location.pathname).replace(/\/$/,'')));
      if(current){
        for(const price of document.querySelectorAll('.summary .price'))price.textContent=new Intl.NumberFormat('th-TH',{style:'currency',currency:'THB'}).format(current.price_satang/100);
        for(const stock of document.querySelectorAll('.summary .stock'))stock.textContent=current.active&&current.stock>0?'พร้อมส่ง '+current.stock+' ชิ้น':'สินค้าหมด / ไม่พร้อมขาย';
        for(const form of document.querySelectorAll('form.cart')){const a=document.createElement('a');a.className='button';a.href=current.active?url('shop/product/'+current.id):url('shop');a.textContent='ดูสต็อกและสั่งซื้อสินค้า';form.replaceWith(a);}
      }
    }).catch(()=>{});
  }
  api('bootstrap').then(config=>{
    widgetConfig = config;
    if(!config.bot_enabled || config.web_widget_enabled === false) { launch.hidden=true; return; }
    if(config.web_widget_title) {
      const headerTitle = root.querySelector('.sn-chat header strong');
      if(headerTitle) headerTitle.textContent = config.web_widget_title;
    }
    root.querySelector('.sn-ai').hidden=!config.ai_enabled;
    if(config.web_default_ai) {
      const aiConsent = root.querySelector('input[name="ai_consent"]');
      if(aiConsent) aiConsent.checked = true;
    }
    if(!config.analytics_enabled)return;
    const track=()=>api('track',{consent:'1',path:location.pathname,referrer:document.referrer,source:new URLSearchParams(location.search).get('utm_source')||''}).catch(()=>{});
    let choice;try{choice=localStorage.getItem('sn-analytics');}catch{}
    const setChoice=value=>{try{localStorage.setItem('sn-analytics',value);}catch{}if(value==='yes')track();};
    const preferences=document.createElement('button');preferences.className='sn-privacy';preferences.textContent='ตั้งค่าสถิติ';root.append(preferences);
    const ask=()=>{if(root.querySelector('.sn-consent'))return;const el=document.createElement('div');el.className='sn-consent';el.innerHTML='<span>อนุญาตเก็บสถิติการเข้าเว็บเพื่อปรับปรุงบริการหรือไม่?</span><button data-choice="yes">ยินยอม</button><button data-choice="no">ไม่ยินยอม</button>';el.addEventListener('click',e=>{const value=e.target.dataset.choice;if(value){setChoice(value);el.remove();}});root.append(el);};
    preferences.addEventListener('click',ask);if(!choice)ask();else if(choice==='yes')track();
  }).catch(()=>{launch.hidden=true;});
})();
