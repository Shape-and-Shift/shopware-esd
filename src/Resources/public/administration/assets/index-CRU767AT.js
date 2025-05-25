const e='<div class="sas-process-bar-container"> <div id="sas-process-bar" class="sas-process-bar" :style="styleObject" ></div> </div>',t={template:e,props:{process:{type:Number,required:!0}},data(){return{styleObject:{width:"0%"}}},watch:{process:s=>{(void 0).styleObject={width:`${s}%`}}}};export{t as default};
//# sourceMappingURL=index-CRU767AT.js.map
