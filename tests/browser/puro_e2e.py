"""Optional browser regression. Run only against a local seeded demo instance.
pip install playwright; playwright install chromium
PURO_BASE_URL=http://127.0.0.1:8000 python tests/browser/puro_e2e.py
Creates demo orders; never sends WhatsApp messages or real payments.
"""
import os,re,json,time
from urllib.parse import urlparse
from pathlib import Path
from playwright.sync_api import sync_playwright,expect

ROOT=Path(__file__).resolve().parents[2]
BASE=os.getenv('PURO_BASE_URL','http://127.0.0.1:8000').rstrip('/')
if urlparse(BASE).hostname not in ['127.0.0.1','localhost']:
 raise RuntimeError('Browser regression is restricted to a localhost demo server.')
OUT=Path(os.getenv('PURO_QA_OUTPUT',str(ROOT/'storage/framework/testing/browser')))
OUT.mkdir(parents=True,exist_ok=True)
PHOTO=ROOT/'public/assets/service-photos/shoe-1.jpg'
REF='E2E'+str(time.time_ns())
errors=[];failures=[];checks=[]
def log(s):
 checks.append(s);print(s,flush=True)
def wire(page):
 page.wait_for_function('window.Livewire !== undefined')
def login(browser,name,viewport=None):
 ctx=browser.new_context(viewport=viewport or {'width':1440,'height':1000})
 page=ctx.new_page();page.on('dialog',lambda d:d.accept())
 page.on('pageerror',lambda e:errors.append(str(e)))
 page.goto(BASE+'/staff/login');wire(page)
 page.get_by_label('Username',exact=True).fill(name)
 page.get_by_label('Password',exact=True).fill('Demo123!')
 page.get_by_role('button',name='Masuk ↗',exact=True).click()
 page.wait_for_url(BASE+'/staff',timeout=15000)
 return ctx,page
def open_order(page,code):
 page.get_by_role('link',name=re.compile(code)).click()
 expect(page.locator('h1')).to_contain_text('Woman & Kids Shoes')
def upload(page,label):
 field=page.get_by_label(label,exact=True)
 field.evaluate("el=>{delete el.dataset.qaUploadComplete;el.addEventListener('livewire-upload-finish',()=>el.dataset.qaUploadComplete='1',{once:true})}")
 field.set_input_files(str(PHOTO))
 expect(field).to_have_attribute('data-qa-upload-complete','1',timeout=20000)
def no_overflow(page,name):
 delta=page.evaluate('document.documentElement.scrollWidth-window.innerWidth')
 assert delta<=1,f'{name}: horizontal overflow {delta}px'
def run():
 with sync_playwright() as pw:
  opts={'headless':True,'args':['--no-sandbox','--disable-dev-shm-usage']}
  if Path('/usr/bin/chromium').exists():opts['executable_path']='/usr/bin/chromium'
  browser_env=os.environ.copy();browser_env.pop('LD_LIBRARY_PATH',None);opts['env']=browser_env
  browser=pw.chromium.launch(**opts)
  ctx=browser.new_context(viewport={'width':1440,'height':1000})
  guest=ctx.new_page();guest.on('pageerror',lambda e:errors.append(str(e)))
  guest.on('response',lambda r:failures.append({'url':r.url,'status':r.status}) if r.status>=500 and r.url.startswith(BASE) else None)
  guest.goto(BASE+'/staff/login');expect(guest.get_by_text(re.compile('Demo lokal:'))).to_be_visible()
  guest.goto(BASE);wire(guest);expect(guest.locator('h1')).to_contain_text('Professional')
  guest.screenshot(path=str(OUT/'01-home-desktop.png'),full_page=True)
  guest.evaluate("window.__puroNavigateProof='same-document'")
  guest.locator('nav.public-nav').get_by_role('link',name='Layanan',exact=True).click()
  guest.wait_for_url(BASE+'/layanan');expect(guest.locator('.service-card')).to_have_count(17)
  assert guest.evaluate('window.__puroNavigateProof')=='same-document'
  log('PASS: 17 services and wire:navigate retains the document')
  guest.goto(BASE+'/book?service=2');wire(guest)
  guest.get_by_label('Nama penerima',exact=True).fill('Customer Browser Demo')
  guest.get_by_label('Nomor WhatsApp',exact=True).fill('081234567890')
  guest.get_by_label('Jenis / bahan / warna',exact=True).fill('Sneakers putih untuk uji browser')
  guest.get_by_role('button',name='Buat pesanan & simpan kode ↗',exact=True).click()
  guest.wait_for_url(re.compile('/track/PURO-'),timeout=20000)
  code=guest.locator('.tracking-token').inner_text().strip()
  expect(guest.get_by_text('Booking sudah tercatat',exact=True)).to_be_visible()
  assert code in guest.evaluate("localStorage.getItem('puro-recent-orders')")
  log('PASS: guest booking, code from booking, recent orders persisted')
  guest.get_by_label('Nomor referensi transaksi',exact=True).fill(REF)
  upload(guest,'Bukti pembayaran')
  guest.get_by_label(re.compile('Saya sudah membayar sesuai tagihan')).check()
  guest.get_by_role('button',name='Kirim bukti pembayaran ↗',exact=True).click()
  expect(guest.get_by_text('Bukti sedang diperiksa admin',exact=True)).to_be_visible(timeout=20000)
  ac,admin=login(browser,'admin01');open_order(admin,code)
  admin.get_by_label('Alasan jika bukti ditolak',exact=True).fill('Foto bukti perlu diperjelas untuk pengujian ulang.')
  admin.get_by_role('button',name='Tolak bukti · izinkan kirim ulang',exact=True).click()
  expect(admin.get_by_text('Bukti ditolak. Pesanan masih aktif.',exact=True)).to_be_visible()
  guest.bring_to_front()
  expect(guest.get_by_text('Perbaiki bukti, pesanan tetap aktif',exact=True)).to_be_visible(timeout=20000)
  guest.get_by_label('Nomor referensi transaksi',exact=True).fill(REF)
  upload(guest,'Bukti pembayaran')
  guest.get_by_label(re.compile('Saya sudah membayar sesuai tagihan')).check()
  guest.get_by_role('button',name='Kirim ulang bukti ↗',exact=True).click()
  expect(guest.get_by_text('Bukti sedang diperiksa admin',exact=True)).to_be_visible(timeout=20000)
  admin.bring_to_front()
  expect(admin.get_by_label('Ketik ulang nomor referensi',exact=True)).to_be_visible(timeout=20000)
  admin.get_by_label('Ketik ulang nomor referensi',exact=True).fill(REF)
  admin.get_by_label(re.compile('Nominal dan referensi sudah cocok')).check()
  admin.get_by_role('button',name='Setujui pembayaran',exact=True).click()
  expect(admin.get_by_text('Pembayaran diverifikasi.',exact=True)).to_be_visible(timeout=20000)
  guest.bring_to_front()
  expect(guest.get_by_text('Pembayaran sudah diverifikasi',exact=True)).to_be_visible(timeout=20000)
  assert guest.locator('.tracking-token').inner_text().strip()==code
  log('PASS: cross-session polling, rejection, same-reference retry, manual verification')
  admin.bring_to_front();upload(admin,'Foto penerimaan')
  admin.get_by_role('button',name='Barang sudah diterima',exact=True).click()
  expect(admin.get_by_label('Teknisi cabang',exact=True)).to_be_visible(timeout=15000)
  option=admin.get_by_label('Teknisi cabang',exact=True).locator('option').filter(has_text='Teknisi Cabang 01').first.get_attribute('value')
  admin.get_by_label('Teknisi cabang',exact=True).select_option(option)
  admin.get_by_role('button',name='Simpan penugasan',exact=True).click()
  tc,tech=login(browser,'teknisi01');open_order(tech,code)
  upload(tech,'Foto sebelum');tech.get_by_role('button',name='Mulai pengerjaan ↗',exact=True).click()
  expect(tech.get_by_label('Foto sesudah',exact=True)).to_be_visible(timeout=15000)
  upload(tech,'Foto sesudah');tech.get_by_label('Catatan hasil',exact=True).fill('Treatment demo sudah selesai dan siap diperiksa.')
  tech.get_by_role('button',name='Kirim hasil ke QC ↗',exact=True).click()
  qc_ctx,qc=login(browser,'qc01');open_order(qc,code)
  qc.get_by_label('Hasil pemeriksaan / instruksi rework',exact=True).fill('Hasil bersih sesuai standar pada pengujian browser.')
  qc.get_by_role('button',name='Lulus QC',exact=True).click()
  expect(qc.locator('.page-intro .pill')).to_have_text('Siap diambil',timeout=15000)
  admin.bring_to_front()
  expect(admin.get_by_role('button',name='Tandai sudah diambil',exact=True)).to_be_visible(timeout=20000)
  admin.get_by_role('button',name='Tandai sudah diambil',exact=True).click()
  guest.bring_to_front();expect(guest.locator('.order-heading .pill')).to_have_text('Pesanan selesai',timeout=20000)
  log('PASS: reception, technician before/after, QC, collection, COMPLETE')
  rc,root=login(browser,'superadmin')
  for tab in ['catalog','branches','qris','accounts','permissions','promos','reports','knowledge']:
   response=root.goto(BASE+'/staff/manage/'+tab);assert response.status==200,(tab,response.status)
   no_overflow(root,'desktop-'+tab)
   if tab in ['catalog','branches','accounts','promos']:
    root.locator('.row-selector').first.click();expect(root.get_by_text('Perbarui data',exact=True)).to_be_visible()
  log('PASS: all management screens, reports and edit forms render')
  bc,bad=login(browser,'admin02');assert bad.goto(admin.url).status==403
  log('PASS: other branch cannot open order')
  for width in [360,390,768]:
   mc=browser.new_context(viewport={'width':width,'height':844});mobile=mc.new_page();mobile.on('pageerror',lambda e:errors.append(str(e)))
   for url,label in [('/','home'),('/layanan','services'),('/book?service=2','booking'),('/lokasi','branches'),('/panduan','guide'),('/track/'+code,'tracking')]:
    response=mobile.goto(BASE+url);assert response.status==200,(width,url,response.status);wire(mobile);no_overflow(mobile,f'{width}-{label}')
    if width==390 and label in ['home','services','booking','tracking']:mobile.screenshot(path=str(OUT/f'02-mobile-{label}.png'),full_page=True)
   if width==360:
    mobile.get_by_role('button',name='Bantuan PURO',exact=True).click()
    mobile.get_by_label('Tulis pertanyaan',exact=True).fill('alo');mobile.get_by_role('button',name='Kirim pertanyaan',exact=True).click()
    expect(mobile.locator('.chat-message.assistant').last).to_contain_text('Halo!')
    mobile.get_by_label('Tulis pertanyaan',exact=True).fill('Saya perlu admin untuk pembayaran');mobile.get_by_role('button',name='Kirim pertanyaan',exact=True).click()
    expect(mobile.locator('.chat-handoff').last).to_be_visible();no_overflow(mobile,'360-chat')
    mobile.screenshot(path=str(OUT/'03-mobile-chat.png'))
    mobile.reload();wire(mobile);mobile.get_by_role('button',name='Bantuan PURO',exact=True).click();expect(mobile.locator('.chat-handoff').last).to_be_visible()
   mc.close()
  log('PASS: mobile 360/390/768px, chatbot greeting/fallback and browser history')
  root.set_viewport_size({'width':390,'height':844})
  for tab in ['catalog','branches','qris','accounts','permissions','promos','reports','knowledge']:
   assert root.goto(BASE+'/staff/manage/'+tab).status==200;no_overflow(root,'mobile-admin-'+tab)
  assert not errors,errors
  assert not failures,failures
  log('PASS: no JavaScript exceptions, no app HTTP 5xx, no horizontal page overflow')
  (OUT/'result.json').write_text(json.dumps({'checks':checks,'javascript_errors':errors,'http_5xx':failures,'tracking_code':code},indent=2))
  browser.close()
if __name__=='__main__':run()
