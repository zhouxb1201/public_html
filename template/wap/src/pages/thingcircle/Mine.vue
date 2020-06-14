<template>
  <div class="thingcirle-mine">
    <Header :info="userInfo" />
    <div class="tab">
      <van-tabs v-model="active" @change="onTab">
        <van-tab :title="item.title" v-for="(item,index) in tabTitle" :key="index"></van-tab>
      </van-tabs>
    </div>
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{message: '暂无干货', showFoot: false}"
      @load="loadList"
    >
      <Card v-if="isShow == 1" :items="list" />
      <DailyGroup v-else :items="list" />
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import Card from "./component/Card";
import Header from "./component/Header";
import DailyGroup from "./component/DailyGroup";
import {
  GET_THINGCIRCLEUSERINFO,
  GET_THINGCIRCLEUSERLIST,
  GET_SHAREINFO
} from "@/api/thingcircle";
import { filterUriParams } from "@/utils/util";
import { list } from "@/mixins";
export default sfc({
  name: "thingcirle-mine",
  data() {
    return {
      active: 0,
      isShow: 1,
      params: {
        page_index: 1,
        page_size: 10
      },
      userInfo: {},
      tabTitle: [
        {
          title: "干货 "
        },
        {
          title: "收藏 "
        },
        {
          title: "点赞 "
        }
      ],
      flag: true
    };
  },
  mixins: [list],
  mounted() {
    this.loadList();
    this.getUserInfo();
    this.getShareInfo();
  },
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_THINGCIRCLEUSERLIST($this.params).then(({ data }) => {
        $this.isShow = data.display_model;
        $this.flag = true;
        let list = data.data;

        $this.pushToList(list, data.page_count, init);
      });
    },
    onTab(index) {
      const $this = this;
      $this.params.thing_option = index + 1;
      if ($this.flag == false) {
        return false;
      }
      $this.flag = false;
      $this.loadList("init");
    },
    getUserInfo() {
      const $this = this;
      GET_THINGCIRCLEUSERINFO().then(({ data }) => {
        $this.userInfo = data;
      });
    },
    getShareInfo() {
      const $this = this;
      GET_SHAREINFO().then(({ data }) => {
        $this.onShare({
          title: data.other_title ? data.other_title : "好物圈",
          desc: data.other_describe
            ? data.other_describe
            : `我刚刚在${$this.$store.getters.config.mall_name}发现了一个很不错的好物圈，赶快来看看吧。`,
          imgUrl: data.other_pic,
          link:
            $this.$store.state.domain +
            "/wap" +
            $this.$route.path +
            filterUriParams($this.$route.query, "extend_code")
        });
      });
    }
  },
  components: {
    Header,
    Card,
    DailyGroup
  }
});
</script>

<style scoped>
.tab {
  margin-top: 10px;
}
.list {
  position: relative;
}
.list >>> .empty {
  top: 120px;
}
</style>