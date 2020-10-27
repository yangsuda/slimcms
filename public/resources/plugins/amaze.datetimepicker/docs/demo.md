---
title: Amaze UI Datetime Picker
---

## Datetime Picker 演示
---

### 默认 input

`````html
<input size="16" type="text" value="2015-02-14 14:45" readonly class="form-datetime am-form-field">

<script>
  $(function() {
    $('.form-datetime').datetimepicker({format: 'yyyy-mm-dd hh:ii'});
  });
</script>
`````
```html
<input size="16" type="text" value="2015-02-14 14:45" readonly class="form-datetime am-form-field">

<script>
  $(function() {
    $('.form-datetime').datetimepicker({format: 'yyyy-mm-dd hh:ii'});
  });
</script>
```

### 设置语言

`````html
<input size="16" type="text" value="2015-02-14 14:45" readonly class="form-datetime-lang am-form-field">
`````
```html
<input size="16" type="text" value="2015-02-14 14:45" readonly class="form-datetime-lang am-form-field">

<script>
(function($){
  // 也可以在页面中引入 amazeui.datetimepicker.zh-CN.js
  $.fn.datetimepicker.dates['zh-CN'] = {
    days: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期日"],
    daysShort: ["周日", "周一", "周二", "周三", "周四", "周五", "周六", "周日"],
    daysMin:  ["日", "一", "二", "三", "四", "五", "六", "日"],
    months: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
    monthsShort: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
    today: "今日",
    suffix: [],
    meridiem: ["上午", "下午"]
  };

  $('.form-datetime-lang').datetimepicker({
    language:  'zh-CN',
    format: 'yyyy-mm-dd hh:ii'
  });
}(jQuery));
</script>
```

### 组件结合

结合 Amaze UI Class `am-input-group` 来实现组件样式，其中 Class `date`、`add-on`、`icon-th` 都在原 JS 中有引用，使用时务必写上。

`````html
<div class="am-input-group date form_datetime-1">
  <input size="16" type="text" value="2015-02-14 14:45" class="am-form-field" readonly>
  <span class="am-input-group-label add-on"><i class="icon-th am-icon-calendar"></i></span>
</div>

<script>
  $(function() {
    $('.form_datetime-1').datetimepicker({
      format: 'yyyy-mm-dd hh:ii'
    })
  })
</script>
`````

```html
<div class="am-input-group date form_datetime-1">
  <input size="16" type="text" value="2015-02-14 14:45" class="am-form-field" readonly>
  <span class="am-input-group-label add-on"><i class="icon-th am-icon-calendar"></i></span>
</div>

<script>
  $(function() {
    $('.form_datetime-1').datetimepicker({
      format: 'yyyy-mm-dd hh:ii'
    })
  })
</script>
```

### 内联调用

`````html
<div id="datetimepicker"></div>

<script>
  $(function() {
    $('#datetimepicker').datetimepicker();
  })
</script>
`````

```html
<div id="datetimepicker"></div>

<script>
  $(function() {
    $('#datetimepicker').datetimepicker();
  })
</script>
```

### 位置设置

HTML 和 组件结合使用一样，`pickerPosition` 控制 datetimepicker 的位置，可以查看 API 详细说明。

`````html
<div class="am-input-group date form_datetime-2">
  <input size="16" type="text" value="2015-02-14 14:45" class="am-form-field" readonly>
  <span class="am-input-group-label add-on"><i class="icon-th am-icon-calendar"></i></span>
</div>

<script>
  $(function() {
    $('.form_datetime-2').datetimepicker({
        format: 'yyyy-mm-dd hh:ii',
        autoclose: true,
        todayBtn: true,
        pickerPosition: 'bottom-left'
    });
  })
</script>
`````

```html
<div class="am-input-group date form_datetime-2">
  <input size="16" type="text" value="2015-02-14 14:45" class="am-form-field" readonly>
  <span class="am-input-group-label add-on"><i class="icon-th am-icon-calendar"></i></span>
</div>

<script>
  $(function() {
    $('.form_datetime-2').datetimepicker({
        format: 'yyyy-mm-dd hh:ii',
        autoclose: true,
        todayBtn: true,
        pickerPosition: 'bottom-left'
    });
  })
</script>
```
### 带删除的组件应用

基于 Class `icon-remove` 重置 `<input>` 的值，Class `am-icon-*` 提供图片样式。

`````html
<div class="am-input-group date form_datetime-3" data-date="2015-02-14 14:45">
  <input size="16" type="text" value="" class="am-form-field" readonly>
  <span class="am-input-group-label add-on"><i class="icon-remove am-icon-close"></i></span>
  <span class="am-input-group-label add-on"><i class="icon-th am-icon-calendar"></i></span>
</div>

<script type="text/javascript">
  $(function() {
    $('.form_datetime-3').datetimepicker({
        format: 'yyyy-mm-dd hh:ii',
        autoclose: true,
        todayBtn: true,
        startDate: '2015-02-14 14:45',
        minuteStep: 10
    });
  });
</script>
`````
```html
<div class="am-input-group date form_datetime-3" data-date="2015-02-14 14:45">
  <input size="16" type="text" value="" class="am-form-field" readonly>
  <span class="am-input-group-label add-on"><i class="icon-remove am-icon-close"></i></span>
  <span class="am-input-group-label add-on"><i class="icon-th am-icon-calendar"></i></span>
</div>

<script type="text/javascript">
  $(function() {
    $('.form_datetime-3').datetimepicker({
        format: 'yyyy-mm-dd hh:ii',
        autoclose: true,
        todayBtn: true,
        startDate: '2015-02-14 14:45',
        minuteStep: 10
    });
  });
</script>
```
### 镜像日期

HTML 和 前面组件一样，增加一个镜像的 `<input>` 元素，调用时设置 `linkField:` `'your-mirror-id'`、`linkFormat: ` `'your-mirror-format'`。

`````html
<div class="am-input-group date form_datetime-4" data-date="2015-02-14 14:45">
  <input size="16" type="text" value="2015-02-14 14:45" class="am-form-field" readonly>
  <span class="am-input-group-label add-on"><i class="icon-remove am-icon-close"></i></span>
  <span class="am-input-group-label add-on"><i class="icon-th am-icon-calendar"></i></span>
</div>

<p><input type="text" id="mirror_field" class="am-form-field" placeholder="镜像的日期" readonly /></p>

<script type="text/javascript">
  $(function() {
    $('.form_datetime-4').datetimepicker({
        format: 'yyyy-mm-dd hh:ii',
        linkField: 'mirror_field',
        linkFormat: 'yyyy-mm-dd hh:ii'
    });
  });
</script>
`````
```html
<div class="am-input-group date form_datetime-4" data-date="2015-02-14 14:45">
  <input size="16" type="text" value="2015-02-14 14:45" class="am-form-field" readonly>
  <span class="am-input-group-label add-on"><i class="icon-remove am-icon-close"></i></span>
  <span class="am-input-group-label add-on"><i class="icon-th am-icon-calendar"></i></span>
</div>

<p><input type="text" id="mirror_field" class="am-form-field" placeholder="镜像的日期" readonly /></p>

<script type="text/javascript">
  $(function() {
    $('.form_datetime-4').datetimepicker({
        format: 'yyyy-mm-dd hh:ii',
        linkField: 'mirror_field',
        linkFormat: 'yyyy-mm-dd hh:ii'
    });
  });
</script>
```
### Today 按钮

调用时设置 `todayBtn: true`。

`````html
<div class="am-input-group date form_datetime-5" data-date="2015-02-14 14:45">
  <input size="16" type="text" value="2015-02-14 14:45" class="am-form-field" readonly>
  <span class="am-input-group-label add-on"><i class="icon-remove am-icon-close"></i></span>
  <span class="am-input-group-label add-on"><i class="icon-th am-icon-calendar"></i></span>
</div>

<script>
  $(function() {
    $('.form_datetime-5').datetimepicker({
        format: 'yyyy-mm-dd HH:ii P',
        showMeridian: true,
        autoclose: true,
        todayBtn: true
    });
  });
</script>
`````
```html
<div class="am-input-group date form_datetime-5" data-date="2015-02-14 14:45">
  <input size="16" type="text" value="2015-02-14 14:45" class="am-form-field" readonly>
  <span class="am-input-group-label add-on"><i class="icon-remove am-icon-close"></i></span>
  <span class="am-input-group-label add-on"><i class="icon-th am-icon-calendar"></i></span>
</div>

<script>
  $(function() {
    $('.form_datetime-5').datetimepicker({
        format: 'yyyy-mm-dd HH:ii P',
        showMeridian: true,
        autoclose: true,
        todayBtn: true
    });
  });
</script>
```

<script src="../js/amazeui.datetimepicker.js"></script>

<script>
  (function($){
    // 只需要添加一次
    $.fn.datetimepicker.dates['zh-CN'] = {
      days: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期日"],
      daysShort: ["周日", "周一", "周二", "周三", "周四", "周五", "周六", "周日"],
      daysMin:  ["日", "一", "二", "三", "四", "五", "六", "日"],
      months: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
      monthsShort: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
      today: "今日",
      suffix: [],
      meridiem: ["上午", "下午"]
    };

    $('.form-datetime-lang').datetimepicker({
      language:  'zh-CN',
      format: 'yyyy-mm-dd hh:ii'
    });
  }(jQuery));
</script>
