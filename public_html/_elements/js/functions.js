function getDonateValue()
{
	var rad_val = 0
	if (document.form1.donateOther.value == "") {
		
for (var i=0; i < document.form1.donate.length; i++)
   {
   if (document.form1.donate[i].checked)
      {
      rad_val = document.form1.donate[i].value;
	  
      }
   }
   
	}
	else {
		rad_val = document.form1.donateOther.value
		}
   
    document.form1.donateTotal.value = rad_val
   
   doTotal();
   
}


function getDinnerValue() {
var DinTot
DinTot = Number(document.form1.numTickets.value) * 45

document.form1.numTicketsTotal.value = DinTot;
doTotal();  // This line calls the next function

}







function doTotal() {

var nonMember = 0
if (document.form1.nonMemberFee.value != ""){
	nonMember = Number(document.form1.nonMemberFee.value)
}
else {
	nonMember = 0
	}

document.form1.amount.value =  Number(document.form1.numTicketsTotal.value) + nonMember + Number(document.form1.donateTotal.value)
}


 function clear_radio_buttons() {
     for (var i = 0; i < document.form1.trailer.length; i++) {
          document.form1.trailer[i].checked = false;
     }
}
