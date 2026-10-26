document.addEventListener('click', async event => {
  const button=event.target.closest('[data-copy-target]');
  if(!button)return;
  const field=document.getElementById(button.dataset.copyTarget);
  if(!field)return;
  field.select();
  try{
    await navigator.clipboard.writeText(field.value);
    button.textContent='คัดลอกแล้ว';
  }catch{
    button.textContent='เลือกข้อความแล้ว กด Ctrl+C เพื่อคัดลอก';
  }
});
