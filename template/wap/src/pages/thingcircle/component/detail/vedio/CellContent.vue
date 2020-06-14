<template>
  <div class="theme-wrap" v-if="items">
    <h2 class="title" v-if="items.title">{{items.title}}</h2>
    <div class="content" :class="flexCol" v-if="items.content">
      <div :class="collapse">{{items.content}}</div>
      <div
        @click="showmoreDesc(items.content)"
        :class="tr"
        v-if="isDes < items.content.length"
      >{{descStatusText}}</div>
    </div>
    <div class="tag">
      <div v-if="items.topic_title">
        <span class="bol">#</span>
        <span class="text">{{items.topic_title}}</span>
      </div>
      <div class="sec" v-if="items.location">
        <van-icon name="location" size="12px" />
        <span class="text">{{items.location}}</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      collapse: "text",
      descStatusText: "展开",
      flexCol: "",
      tr: ""
    };
  },
  props: {
    items: [Object]
  },
  computed: {
    isDes() {
      let len = parseInt((document.body.offsetWidth - 20) / 14);
      return len;
    }
  },
  methods: {
    showmoreDesc(content) {
      if (this.descStatusText == "展开") {
        this.descStatusText = "收起";
        this.collapse = "";
        this.tr = "tr";
        this.flexCol = "flex-column";
      } else {
        this.descStatusText = "展开";
        this.collapse = "text";
        this.tr = "";
        this.flexCol = "";
      }
    }
  }
};
</script>

<style scoped>
.theme-wrap {
  position: relative;
  padding: 10px 14px 20px 14px;
  overflow: hidden;
  color: #fff;
}
.theme-wrap .title {
  font-size: 15px;
}
.theme-wrap .content {
  font-size: 14px;
  line-height: 18px;
  padding-top: 8px;
  display: flex;
}
.theme-wrap .content.flex-column {
  flex-direction: column;
}
.theme-wrap .content .text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
}
.theme-wrap .content .tr {
  text-align: right;
  padding-right: 10px;
}
.theme-wrap .tag {
  display: flex;
  overflow: hidden;
  margin-top: 20px;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.theme-wrap .tag div {
  background-color: rgba(255, 255, 255, 0.3);
  border-radius: 20px;
  text-align: center;
  height: 20px;
  font-size: 12px;
  padding: 0px 10px;
  line-height: 20px;
  color: #fff;
  margin-right: 10px;
  display: flex;
  align-items: center;
  max-width: 50%;
}
.theme-wrap .tag div span.text {
  display: inline-block;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.theme-wrap .tag .bol {
  background-color: #ff8c00;
  border-radius: 4px;
  width: 14px;
  height: 14px;
  line-height: 14px;
  text-align: center;
  display: inline-block;
  margin-right: 4px;
}
.theme-wrap .tag .sec {
  display: flex;
  align-items: center;
  max-width: 45%;
}
.theme-wrap .tag .sec >>> .van-icon {
  background-color: #6badea;
  border-radius: 4px;
  padding: 2px;
}
.theme-wrap .tag .sec span {
  display: inline-block;
  margin-left: 4px;
}
</style>