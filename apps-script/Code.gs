const SPREADSHEET_ID = 'PASTE_YOUR_GOOGLE_SHEET_ID';
const SECRET_TOKEN = 'PASTE_THE_SAME_SECRET_TOKEN_USED_IN_OPD_SOFTWARE';
const SHEET_NAME = 'Patients';

function doPost(e) {
  try {
    const body = JSON.parse(e.postData.contents || '{}');
    if (body.token !== SECRET_TOKEN) return json_({ok:false,error:'unauthorized'});
    if (body.action !== 'upsert_patient' || !body.patient) return json_({ok:false,error:'bad_request'});

    const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
    const sheet = ss.getSheetByName(SHEET_NAME) || ss.insertSheet(SHEET_NAME);
    const headers = ['patient_id','uhid','name','age','sex','guardian','contact_number','address','bill_no','visit_date','visit_time','panel','doctor_dept','room_no','app_no','created_by','created_at','updated_at','synced_at'];
    if (sheet.getLastRow() === 0) sheet.appendRow(headers);

    const p = body.patient;
    const row = headers.map(h => h === 'synced_at' ? new Date() : (p[h] ?? ''));
    const ids = sheet.getLastRow() > 1 ? sheet.getRange(2,1,sheet.getLastRow()-1,1).getValues().flat() : [];
    const idx = ids.findIndex(v => String(v) === String(p.patient_id));
    if (idx >= 0) sheet.getRange(idx + 2, 1, 1, row.length).setValues([row]);
    else sheet.appendRow(row);
    return json_({ok:true});
  } catch (err) { return json_({ok:false,error:String(err)}); }
}
function json_(obj) { return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON); }
