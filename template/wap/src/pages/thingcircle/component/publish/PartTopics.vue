<template>
  <div>
    <van-cell
      :title="title"
      class="nowrap"
      :value="text"
      is-link
      @click="showList = true"
      :title-class="title != '参与话题' ? color.yes : color.no"
    >
      <van-icon
        slot="icon"
        :style="{backgroundColor:title != '参与话题' ? '#6790db' : '#cccccc'}"
        class="cell-left-icon"
        name="v-icon-topic"
        size="10px"
      />
    </van-cell>
    <van-popup v-model="showList" position="bottom" class="part-popup">
      <HeadSearch
        :disabled="false"
        :placeholder="placeholder"
        showLeft
        show-action
        :leftClick="leftClick"
        @rightAction="getSearchInfo"
      />
      <div class="part-list">
        <div class="part-tab-nav" v-if="topic_state == 1">
          <div
            class="tab"
            v-for="(item,index) in tab_list"
            :key="index"
            @click="onTab(item.topic_id)"
          >
            <img :src="item.topic_pic ? item.topic_pic : ''" :onerror="$ERRORPIC.noGoods" />
            <p>{{item.topic_title}}</p>
          </div>
        </div>
        <van-cell title="不参与任何话题" class="opt" @click="getTopicsInfo">
          <div slot="icon" class="left-icon"></div>
        </van-cell>
        <TopicsList
          :id="topic_id"
          :search_text="search_text"
          @add-topics="getTopicsInfo"
          :state="topic_state"
        />
      </div>
    </van-popup>
  </div>
</template>

<script>
import HeadSearch from "@/components/HeadSearch";
import TopicsList from "./TopicsList";
import { Search } from "vant";
import { GET_TOPICLIST, GET_NEXTTOPICLIST } from "@/api/thingcircle";
export default {
  data() {
    return {
      showList: false,
      placeholder: "请输入搜索关键字",
      title: "参与话题",
      text: "选择合适的话题会有更多赞",
      color: {
        yes: "cr-ff454e",
        no: "cr-323233"
      },
      tab_list: [],
      topic_id: "",
      search_text: "",
      topic_state: "0"
    };
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_TOPICLIST().then(({ data }) => {
        $this.tab_list = data.data;
        $this.topic_state = data.topic_state;
      });
    },
    leftClick() {
      this.showList = false;
    },
    onTab(id) {
      this.topic_id = id;
    },
    getSearchInfo(value) {
      this.search_text = value;
    },
    getTopicsInfo(topics) {
      this.text = topics.title ? "" : "选择合适的话题会有更多赞";
      this.title = topics.title ? topics.title : "参与话题";
      this.showList = false;
      this.$emit("click-topics", topics);
    }
  },
  components: {
    [Search.name]: Search,
    HeadSearch,
    TopicsList
  }
};
</script>

<style scoped>
.part-popup {
  height: 100%;
  border-radius: 0;
}
.nowrap >>> .van-cell__value {
  text-overflow: ellipsis;
  white-space: nowrap;
}
.cr-323233 {
  color: #323233;
}
.cr-ff454e {
  color: #6790db;
}
.part-list {
  height: 100%;
  box-sizing: border-box;
  max-height: calc(100vh - 48px);
  overflow-y: auto;
}
.left-icon {
  width: 15px;
  height: 15px;
  border-radius: 50%;
  border: 1px solid #494848;
  margin-top: 4px;
  position: relative;
  margin-right: 5px;
}
.left-icon::before {
  content: "";
  width: 100%;
  height: 1px;
  background-color: #494848;
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%) rotate(45deg);
}
.part-tab-nav {
  overflow: hidden;
  overflow-x: auto;
  box-sizing: content-box;
  display: flex;
  position: relative;
  background-color: #fff;
  margin: 0px 5px;
}
.opt::before {
  content: "";
  position: absolute;
  pointer-events: none;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  top: 0;
  left: 0;
  width: 100%;
  transform: scaleY(0.5);
  height: 1px;
  background-color: #ebedf0;
}
.part-tab-nav .tab {
  flex-basis: 22%;
  text-align: center;
  padding: 10px 5px;
  color: #999;
}
.part-tab-nav .tab p {
  line-height: 24px;
}
.part-tab-nav .tab img {
  width: 75px;
  height: 45px;
  display: block;
  border-radius: 10px;
}
.cell-left-icon {
  height: 18px;
  line-height: 18px;
  margin-right: 5px;
  background-color: #cccccc;
  border-radius: 50%;
  padding: 0px 3px;
  color: #fff;
  margin-top: 3px;
}
</style>