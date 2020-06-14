<template>
  <div class="thingcirle-home">
    <div class="head">
      <div class="head-content">
        <div class="head-tab">
          <van-tabs v-model="tab_active" @change="onTab">
            <van-tab :title="item.name" v-for="(item,index) in tabs" :key="index"></van-tab>
          </van-tabs>
          <van-icon name="chat-o" size="20px" class="icon" @click="toMessage"/>
        </div>
        <van-search placeholder="大家都在搜" @search="onSearch" v-model="params.search_text"/>
      </div>
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
      <CellInfoGroup :list="list" :isShow="isShow" />
    </List>
  </div>
</template>

<script>
import { Search } from "vant";
import CellInfoGroup from "./component/CellInfoGroup";
import { GET_THINGCIRCLELIST, GET_SHAREINFO } from "@/api/thingcircle";
import { list } from "@/mixins";
import { filterUriParams } from "@/utils/util";
import sfc from "@/utils/create";
export default sfc({
  name: "thingcirle-home",
  data() {
    return {
      tab_active: 1,
      tabs: [
        {
          name: "关注"
        },
        {
          name: "发现"
        },
        {
          name: "附近"
        }
      ],
      params: {
        page_index: 1,
        page_size: 10,
        search_text: "",
        lng: "",
        lat: "",
        follow: ""
      },
      pos: {
        lng: "",
        lat: ""
      },
      isShow: "1"
    };
  },
  mixins: [list],
  mounted() {
    this.location();
    this.loadList();
    this.getShareInfo();
  },
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_THINGCIRCLELIST($this.params).then(({ data }) => {
        let list = data.data;
        $this.isShow = data.display_model;
        $this.pushToList(list, data.page_count, init);
      });
    },
    onTab(index) {
      const $this = this;
      if (index == 0) {
        $this.params.lng = "";
        $this.params.lat = "";
        $this.params.follow = 1;
      } else if (index == 1) {
        $this.params.lng = "";
        $this.params.lat = "";
        $this.params.follow = "";
      } else if (index == 2) {
        $this.params.lng = $this.pos.lng;
        $this.params.lat = $this.pos.lat;
        $this.params.follow = "";
      }
      $this.loadList("init");
    },
    location() {
      const $this = this;
      $this.$store
        .dispatch("getBMapLocation")
        .then(({ location }) => {
          $this.pos.lng = location.lng;
          $this.pos.lat = location.lat;
        })
        .catch(error => {});
    },
    onSearch(){
      this.loadList("init");
    },
    toMessage(){
      this.$router.push("/message");
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
    [Search.name]: Search,
    CellInfoGroup
  }
});
</script>

<style scoped>
.head {
  height: 98px;
}
.head-content {
  position: fixed;
  height: 98px;
  top: 0px;
  z-index: 999;
  left: 0;
  width: 100%;
}
.head-tab {
  background-color: #ffffff;
  width: 100%;
  position: relative;
  height: 44px;
}
.head-tab >>> .van-tabs {
  width: 40%;
  margin: auto;
}
.head-tab .icon {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
}
.head-tab >>> .van-hairline--top-bottom::after {
  border-width: 0px;
}
.head >>> .van-search__content {
  border-radius: 20px;
}
</style>