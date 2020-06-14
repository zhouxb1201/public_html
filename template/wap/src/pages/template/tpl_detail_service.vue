<template>
  <div :class="item.id" :style="viewStyle" v-if="isShow">
    <van-cell is-link @click="click" value-class="value">
      <div class="value-item" v-for="(t,i) in item.data" :key="i">
        <img class="img" :src="t.imgurl" />
        <span class="title" :style="{color:item.style.titlecolor}">{{t.title}}</span>
      </div>
    </van-cell>
    <div>
      <PopupBottom v-model="popupShow" title="服务说明" content-height="auto">
        <van-cell-group>
          <van-cell v-for="(l,index) in item.data" :key="index">
            <img slot="icon" class="img" :src="l.imgurl" />
            <div slot="title" class="title" :style="{color:item.style.titlecolor}">{{l.title}}</div>
            <div slot="label" class="desc" :style="{color:item.style.desccolor}">{{l.desc}}</div>
          </van-cell>
        </van-cell-group>
      </PopupBottom>
    </div>
  </div>
</template>

<script>
import PopupBottom from "@/components/PopupBottom";
import { isEmpty } from "@/utils/util";
export default {
  name: "tpl_detail_serivce",
  data() {
    return {
      popupShow: false
    };
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    isShow() {
      return !isEmpty(this.item.data) && this.item.params.show;
    },
    viewStyle() {
      return {
        marginTop: this.item.style.margintop + "px",
        marginBottom: this.item.style.marginbottom + "px"
      };
    }
  },
  methods: {
    click() {
      this.popupShow = true;
    }
  },
  components: {
    PopupBottom
  }
};
</script>

<style scoped>
.value {
  display: flex;
  align-items: center;
  overflow: hidden;
  white-space: nowrap;
}

.value-item {
  display: flex;
  margin-right: 10px;
  align-items: center;
  white-space: nowrap;
  font-size: 12px;
}

.img {
  display: block;
  width: 24px;
  height: 24px;
  margin-right: 5px;
}

.title {
  color: #323233;
}

.desc {
  color: #909399;
}
</style>
