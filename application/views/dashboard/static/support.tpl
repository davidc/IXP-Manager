{include file="header.tpl"}

{if $user.privs eq 3}
    <ul class="breadcrumb">
        <li>
            <a href="{genUrl}">Home</a> <span class="divider">/</span>
        </li>
        <li>
            Documentation <span class="divider">/</span>
        </li>
        <li class="active">
            Technical Support and Contact Information
        </li>
    </ul>
{else}
    <div class="page-content">
        <div class="page-header">
            <h1>Technical Support</h1>
        </div>
{/if}

<div class="alert alert-info">
<h3 align="center">
    Techical Support: {mailto address="operations@inex.ie"}
    &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
    Billing / Accounts: {mailto address="accounts@inex.ie"}
    &nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;
    Sales / Marketing: {mailto address="sales@inex.ie"}
</h3>
</div>

<p>
Regular technical support at INEX is provided on an office hours basis from 08:00 to 18:00 GMT,
Monday through Friday. The normal communications channel for technical support is email to
<a href="operations@inex.ie">operations@inex.ie</a>. INEX aims for 4 hour turnaround on all
email support requests. INEX operations staff are also available by telephone on +353 1 6169698
and +353 1 685 4220.
<br /><br />
</p>

<h3>Emergency 24x7x365 Support</h3>

<p>
An 24-hour support hotline is available on +353 86 822 9854 or +353 86 801 7669 for emergency
calls which fall outside normal office hours. This support facility is intended for emergencies
only, including:
</p>

<ul>
    <li> INEX critical system failures causing loss of service to members </li>
    <li> Emergency out-of-hours access to INEX cages for members who house routers there </li>
</ul>

<p>
If there is no immediate answer from this phone, please leave a message and it will be
attended to immediately.
</p>

<br /><br />

<div class="well">

    <table border="0" align="center">
    <tr>
        <td width="20"></td>
        <td colspan="3"><h3>Technical Support Summary</h3></td>
    </tr>
    <tr>
        <td></td>
        <td align="right"><strong>Email:</strong></td>
        <td></td>
        <td align="left"><a href="mailto:operations@inex.ie">operations@inex.ie</a></td>
    </tr>
    <tr>
        <td></td>
        <td align="right"><strong>Phone:</strong></td>
        <td></td>
        <td align="left">+353 1 616 9698 or +353 1 685 4220</td>
    </tr>
    <tr>
        <td></td>
        <td align="right"><strong>Hours:</strong></td>
        <td></td>
        <td align="left">09:00 to 18:00 GMT, Monday to Friday</td>
    </tr>
    <tr>
        <td></td>
        <td align="right"><strong>24h Emergency:</strong></td>
        <td></td>
        <td align="left">+353 86 822 9854 and/or +353 86 801 7669</td>
    </tr>
    </table>
</div>


{include file="footer.tpl"}
