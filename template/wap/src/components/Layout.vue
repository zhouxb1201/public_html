<template>
  <div>
    <transition name="van-fade" mode="out-in">
      <div class="loading" v-if="loading">
        <div class="box">
          <van-loading color="white" class="spinner" />
          <div class="text">{{loadingText}}</div>
        </div>
      </div>
    </transition>
    <slot v-if="post" />
    <transition name="van-fade" mode="out-in">
      <Empty
        v-if="error"
        :page-type="errorType"
        :message="errorText"
        :show-foot="showFoot"
        :btn-text="errorBtnText"
        :event="errorBtnEvent"
        :btn-link="btnLink"
        @click="onRefresh"
      />
    </transition>
  </div>
</template>

<script>
import Empty from "./Empty";
import { layout } from "@/mixins";
import { Loading } from "vant";
export default {
  mixins: [layout],
  props: {
    loadingText: {
      type: String,
      default: "加载中"
    }
    // errorText: {
    //   type: String,
    //   default: "数据加载失败，请稍后再试！"
    // },
    // errorBtnText: {
    //   type: String,
    //   default: "刷新"
    // },
    // errorBtnEvent: {
    //   type: Boolean,
    //   default: true
    // },
    // btnLink: {
    //   type: [String, Object]
    // }
  },
  methods: {
    onRefresh() {
      location.reload();
    }
  },
  components: {
    Empty,
    [Loading.name]: Loading
  }
};
</script>

<style scoped>
.loading {
  background: #ffffff;
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 0;
  z-index: 10;
  display: -webkit-box;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-align: center;
  -ms-flex-align: center;
  align-items: center;
  -webkit-box-pack: center;
  -ms-flex-pack: center;
  justify-content: center;
}

.loading .box {
  background: rgba(0, 0, 0, 0.5);
  padding: 10px;
  width: 80px;
  height: 80px;
  border-radius: 4px;
  margin: 10px;
  color: #fff;
  display: flex;
  flex-flow: column;
  align-items: center;
  justify-content: center;
  font-size: 12px;
}

.loading .spinner {
  margin: 10px;
}

.loading .box .text {
  white-space: nowrap;
}
</style>
