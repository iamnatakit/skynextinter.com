(() => {
  document.querySelectorAll('[data-payment-qr]').forEach(container => {
    try {
      const qr=qrcodegen.QrCode.encodeText(container.dataset.paymentQr,qrcodegen.QrCode.Ecc.MEDIUM);
      const ns='http://www.w3.org/2000/svg',svg=document.createElementNS(ns,'svg'),border=4;
      svg.setAttribute('viewBox',`0 0 ${qr.size+border*2} ${qr.size+border*2}`);
      svg.setAttribute('role','img');svg.setAttribute('aria-label','QR พร้อมเพย์ 990 บาท');
      const white=document.createElementNS(ns,'rect');white.setAttribute('width','100%');white.setAttribute('height','100%');white.setAttribute('fill','#fff');svg.append(white);
      const path=document.createElementNS(ns,'path');let d='';
      for(let y=0;y<qr.size;y++)for(let x=0;x<qr.size;x++)if(qr.getModule(x,y))d+=`M${x+border},${y+border}h1v1h-1z `;
      path.setAttribute('d',d);path.setAttribute('fill','#000');svg.append(path);container.append(svg);
    } catch(error) {container.textContent='ไม่สามารถแสดง QR กรุณารีเฟรชหรือติดต่อผู้ดูแล';}
  });
})();
