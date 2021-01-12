basehost = typeof basehost=='undefined'?'?':basehost;
function Enums() {
    var res = navigator.userAgent.toLowerCase().match(/android|iphone/);
    var self = this;
    var evalue, egroup, drawID, validform, css;
    this.init = function(option) {
        self.evalue = (option.evalue == undefined) ? 0 : option.evalue;
        self.egroup = option.egroup;
        self.drawID = option.drawID;
        self.validform = option.validform;
        self.defaultName = option.defaultName;
        self.css = option.css;
        if(self.egroup == undefined || self.drawID == undefined) {
            alert("egroup和drawID不能为空");
            return false;
        }

        if(typeof enums_before === 'function') {
            enums_before(self);
        }

        self.ajax_get_enums_list();
    }
    this.ajax_get_enums_list = function() {
        $.getJSON(basehost+"&p=main/enumsData&egroup=" + self.egroup + "&rand=" + Math.random(), function(result) {
            var _infolist = result.data.list;
            if(Object.keys(_infolist).length>0) {
                if(self.evalue == 0 || self.get_reid(_infolist, self.evalue) == -1) {
                    self.draw_html(_infolist);
                } else {
                    self.draw_html_by_evalue(_infolist, self.evalue);
                }
                self.draw_input();
                if($('select').selected != undefined) {
                    if(res == 'android' || res == 'iphone') {

                    } else {
                        $('#' + self.egroup + '_span select').chosen({ allow_single_deselect: true, disable_search_threshold: 10, search_contains: true, });
                    }
                }
                if(typeof enums_callback === 'function') {
                    enums_callback(self);
                }
            }
        });
    };
    this.draw_input = function() {
        $("#" + self.drawID).append('<input type="hidden" name="' + self.egroup + '" id="' + self.egroup + '" value="' + self.evalue + '"/>');
    };
    this.draw_html = function(infolist, reid, parentreid) {
        reid = (reid == undefined) ? 0 : reid;
        parentreid = (parentreid == undefined) ? 'top' : parentreid;

        if(reid == -1 || reid == '') {
            self.remove_span(parentreid);
        }

        var html_str = "";
        html_str += '<span class="form-group mr-1" id="span_' + self.egroup + parentreid + '">';
        if(self.defaultName) {
            html_str += '<select name="' + self.egroup + '_' + parentreid + '" id="' + self.egroup + '_' + parentreid + '" ' + self.validform + ' class="form-control ' + self.css + '">';
            //html_str += '<select data-am-selected="{btnWidth: \'100%\', noSelectedText: \''+self.defaultName+'\'}" name="'+self.egroup+'_'+parentreid+'" id="'+self.egroup+'_'+parentreid+'" '+self.validform+' class="form-control '+self.css+'">';
            html_str += '<option value="">' + self.defaultName + '</option>';
        } else {

            html_str += '<select name="' + self.egroup + '_' + parentreid + '" id="' + self.egroup + '_' + parentreid + '" ' + self.validform + ' class="form-control ' + self.css + '">';
            //html_str += '<option value=""></option>';
            html_str += '<option value="">请选择..</option>';
        }

        var option_str = "";
        var option_num = 0;
        $.each(infolist, function(k, v) {
            if(v.reid == reid) {
                option_num++;
                option_str += '<option value="' + v.evalue + '">' + v.ename + '</option>';
            }
        });
        html_str += option_str;
        html_str += '</select><span class="Validform_checktip"></span></span>';
        if(reid != 0) {
            self.remove_span(parentreid);
        }
        if(option_str) {
            $("#" + self.drawID).append(html_str);
            $("#" + self.egroup + "_" + parentreid).change(function() {
                var _reid = $(this).val();
                self.draw_html(infolist, _reid, reid);
            });
        }
        $(":input[name='" + self.egroup + "']").val(reid == -1 ? parentreid : reid);
        if($('#' + self.egroup + '_span select').selected != undefined) {
            if(res == 'android' || res == 'iphone') {} else {
                if(option_num > 6) {
                    $('#' + self.egroup + '_span select').chosen({ allow_single_deselect: true, disable_search_threshold: 10, search_contains: true, });
                    //$('#'+self.egroup+'_span select').selected({maxHeight: '200px'});
                } else {
                    $('#' + self.egroup + '_span select').chosen({ allow_single_deselect: true, disable_search_threshold: 10, search_contains: true, });
                    //$('#'+self.egroup+'_span select').selected();
                }
            }
        }
    };
    this.draw_html_by_evalue = function(infolist, evalue) {
        var reid = self.get_reid(infolist, evalue);
        var parentreid = (reid == 0) ? 'top' : self.get_reid(infolist, reid);
        var html_str = "";
        var i = self.check_subid(infolist, evalue);
        html_str += '<span class="form-group mr-1" id="span_' + self.egroup + parentreid + '">';
        if(self.defaultName) {
            html_str += '<select name="' + self.egroup + '_' + parentreid + '" id="' + self.egroup + '_' + parentreid + '" ' + self.validform + ' class="form-control ' + self.css + '">';
            //html_str += '<select data-am-selected="{btnWidth: \'100%\', noSelectedText: \''+self.defaultName+'\'}" name="'+self.egroup+'_'+parentreid+'" id="'+self.egroup+'_'+parentreid+'" '+self.validform+' class="form-control '+self.css+'">';
            html_str += '<option value="">' + self.defaultName + '</option>';
        } else {

            html_str += '<select name="' + self.egroup + '_' + parentreid + '" id="' + self.egroup + '_' + parentreid + '" ' + self.validform + ' class="form-control ' + self.css + '">';
            html_str += '<option value="">请选择..</option>';
        }

        var option_str = "";
        var option_num = 0;
        $.each(infolist, function(k, v) {
            if(v.reid == reid) {
                option_num++;
                if(v.evalue == evalue) {
                    option_str += '<option value="' + v.evalue + '" selected="selected">' + v.ename + '</option>';
                } else {
                    option_str += '<option value="' + v.evalue + '">' + v.ename + '</option>';
                }
            }
        });
        html_str += option_str;
        html_str += '</select></span>';
        if(option_str) {
            $("#" + self.drawID).prepend(html_str);

            $("#" + self.egroup + "_" + parentreid).change(function() {
                var _reid = $(this).val();
                self.draw_html(infolist, _reid, reid);
                //              if(i!=1){
                //                  self.draw_html(infolist,_reid,reid);
                //              }else{
                //                  i=0;    
                //              }
            });
        }
        if(parentreid != 'top') {
            self.draw_html_by_evalue(infolist, reid);
        }
        if($('#' + self.egroup + '_span select').selected != undefined) {
            if(res == 'android' || res == 'iphone') {} else {
                if(option_num > 6) {
                    $('#' + self.egroup + '_span select').chosen({ allow_single_deselect: true, disable_search_threshold: 10, search_contains: true, placeholder_text_single: '请选择' });
                    //$('#'+self.egroup+'_span select').selected({maxHeight: '200px'});
                } else {
                    $('#' + self.egroup + '_span select').chosen({ allow_single_deselect: true, disable_search_threshold: 10, search_contains: true, placeholder_text_single: '请选择' });
                    //$('#'+self.egroup+'_span select').selected();
                }
            }
        }
    };
    this.get_reid = function(infolist, evalue) {
        var reid = -1;
        $.each(infolist, function(k, v) {
            if(v.evalue == evalue) {
                reid = v.reid;
            }
        });
        return reid;
    };
    this.check_subid = function(infolist, evalue) { //判断是否存在下级菜单
        var reid = 0;
        $.each(infolist, function(k, v) {
            if(v.reid == evalue) {
                reid = 1;
            }
        });
        return reid;
    };
    this.remove_span = function(parentreid) {
        $("#span_" + self.egroup + parentreid).nextAll("span").remove();
        $("#span_" + self.egroup + parentreid).remove();
    };
}