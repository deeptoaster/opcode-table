<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
  <head>
    <title>Z80 Opcode Table</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link href="https://clrhome.org/logo.css" type="text/css" rel="stylesheet" />
    <link href="opcode-table.css?v={$date}" type="text/css" rel="stylesheet" />
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-25020274-2"></script>
    <script src="/bin/js/ga.js"></script>
    <script src="opcode-table.js?v={$date}"></script>
  </head>
  <body>
    <header>
      <h1 class="logo">
        <a href="https://clrhome.org/resources/">
          <span>another resource by</span>
          <img src="https://clrhome.org/images/emblem.png" alt="ClrHome" />
        </a>
      </h1>
      <p>Enjoying this service? Consider <a href="https://www.paypal.com/donate/?business=T3NJS3T45WMFC&item_name=Z80+Opcode+Table&currency_code=USD">buying me a beer</a> (or donating to help cover server costs)!</p>
      <p>Want a free, online Z80 assembler where you can save your projects in the cloud? Check out the <a href="https://clrhome.org/asm/">ORG Z80 IDE</a>!</p>
      <p>Made by <a href="https://fishbotwilleatyou.com/">Deep Toaster</a>. Have a suggestion or spot an error? <a href="mailto:deeptoaster@gmail.com">Send me an email</a> or <a href="https://github.com/deeptoaster/opcode-table">open a pull request</a>!</p>
    </header>
    <ul>
      <li>normal instructions</li>
      <li class="link">more bytes</li>
      <li class="undocumented">undocumented</li>
      <li class="z180">Z180 only</li>
    </ul>
{foreach from=$tables item=table}    {include file='table.tpl'}
{/foreach}  </body>
</html>
